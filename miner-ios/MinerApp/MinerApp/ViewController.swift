//
//  ViewController.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 12.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import UIKit
import CryptoKit

class ViewController: UIViewController, ServerConnectorDelegate {
    @IBOutlet weak var clientIDLabel: UILabel!
    @IBOutlet weak var jobIDLabel: UILabel!
    @IBOutlet weak var durationLabel: UILabel!
    @IBOutlet weak var hashrateLabel: UILabel!
    
    private var clientId: Int?
    private var token: String?
    let server = "mining.sattler.cool"
    

    override func viewDidLoad() {
        super.viewDidLoad()
        
        DispatchQueue.main.async {
            self.jobIDLabel.text = "0"
            self.clientIDLabel.text = "0"
            self.durationLabel.text = "00:00"
            self.hashrateLabel.text = "0"
        }
        
        // set ViewController as delegate for ServerConnector
        ServerConnector.shared.delegate = self
        
        // get the API token and only continue here, if clientId and token are set afterwards
        loadAPIToken();
        guard let _ = clientId, let _ = token else {
            return
        }
        
        // request a new mining Job
        ServerConnector.shared.requestMiningJob(token: self.token!)
    }
    
    /**
     Prevent rotation of the UI when the phone rotates
     */
    override open var shouldAutorotate: Bool {
        return false
    }
    
    /**
     Tries to load the clientId and api token from the KeyStore. If the KeyStore does not contain those yet, a new clientId and token are being requested by the server.
     If there are already a clientId and api token stored in the KeyStore, they are loaded and the class variables clientId and token are updated accordingly.
     */
    func loadAPIToken() {
        // query clientId and token from the KeyStore
        let tokenQuery: [String: Any] = [kSecClass as String: kSecClassInternetPassword, kSecAttrServer as String: server, kSecMatchLimit as String: kSecMatchLimitOne, kSecReturnAttributes as String: true, kSecReturnData as String: true]
        
        var item: CFTypeRef?
        let status = SecItemCopyMatching(tokenQuery as CFDictionary, &item)
        guard status != errSecItemNotFound else {
            // no token found in keystore yet. Retrieve new token from server
            ServerConnector.shared.registerClient()
            return
        }
        guard status == errSecSuccess else {
            // something went wrong retrieving the credentials out of the KeyStore. Notify the user.
            DispatchQueue.main.async {
                let alert = UIAlertController(title: "Error", message: "Could not retrieve miner credentials.", preferredStyle: .alert)
                alert.addAction(UIAlertAction(title: "OK", style: .default, handler: nil))
                self.present(alert, animated: true)
            }
            return
        }
        
        guard let existingItem = item as? [String: Any], let tokenData = existingItem[kSecValueData as String] as? Data, let token = String(data: tokenData, encoding: String.Encoding.utf8), let clientIdString = existingItem[kSecAttrAccount as String] as? String, let clientId = Int(clientIdString) else {
            
            // something went wrong, unpacking the data from the keystore. Notify the user.
            DispatchQueue.main.async {
                let alert = UIAlertController(title: "Error", message: "Could not retrieve miner credentials from keystore.", preferredStyle: .alert)
                alert.addAction(UIAlertAction(title: "OK", style: .default, handler: nil))
                self.present(alert, animated: true)
            }
            return
        }
        
        // Store the retreived clientId and token into the class variables clientId and token
        self.clientId = clientId
        self.token = token
        
        // Update clientIDLabel in the Main View to the actual id
        DispatchQueue.main.async {
            self.clientIDLabel.text = String(self.clientId!)
        }
    }
    
    // MARK: Mining logic
    
    /**
     Starts the Mining task.
     First, everything needed for the BlockHeader is being merged together, except for the nonce. The nonce will be increased by 1 for each calculation round. The range, that the miner is supposed to try out different nonces is being transmitted by the server.
     See also:
     [Definition of the Block Header](https://bitcoin.org/en/developer-reference#block-headers)
     [Internal byte order of Bitcoin header](https://bitcoin.org/en/glossary/internal-byte-order)
     */
    func startMining(miningJob: MiningJob){
        let blockHeader = miningJob.blockHeader
        
        // show the current Job Id in the UI
        DispatchQueue.main.async {
            self.jobIDLabel.text = String(miningJob.jobId)
        }
        
        // Encode UInt32 values into Data objects
        let versionData = blockHeader.version.data()
        let timeStampData = blockHeader.timestamp.data()
        let nbitsData = blockHeader.nbits.data()
        
        // Encode Hex String for prevBlockHash and merkleRoot as Data objects. The byte order is being reversed to match the internal byte order of the Bitcoin header.
        let prevBlockHashData = Data(Data(blockHeader.prevBlockHash.hexDecodedData()).reversed())
        let merkleRootData = Data(Data(blockHeader.merkleRoot.hexDecodedData()).reversed())
        
        // Create the Bitcoin block header without the nonce
        let headerData = NSMutableData()
        headerData.append(versionData)
        headerData.append(prevBlockHashData)
        headerData.append(merkleRootData)
        headerData.append(timeStampData)
        headerData.append(nbitsData)
        
        // prepare variables for results. They are only being set, if a valid solution is found.
        var resultNonce: UInt32?
        var resultHashHex: String?
        
        // setup the timer that will increase the duration each second and recalculate the hash rate.
        var duration:UInt32 = 0
        var timer: Timer?
        var noncesTried: UInt32 = 0
        DispatchQueue.main.async {
            timer = Timer.scheduledTimer(withTimeInterval: 1.0, repeats: true, block: { timer in
                // count time since hashing started
                duration += 1
                let seconds = duration % 60
                let minutes = duration / 60
                
                // display duration and hash rate in the UI
                self.durationLabel.text = String(format: "%02d:%02d", minutes, seconds)
                self.hashrateLabel.text = String(noncesTried / duration)
                
            })
            timer!.fire()
        }
        
        
        // Here, the actual hash computation takes place
        for n in miningJob.startNonce...miningJob.endNonce {
            // create a hard copy of the prepared Header and append the current nonce value
            let headerCopy = NSMutableData()
            headerCopy.setData(headerData as Data)
            headerCopy.append(n.data())
            
            // Perform two rounds of sha265 hashes, using Apple CryptoKit
            let secondRoundDigest = SHA256.hash(data: SHA256.hash(data: headerCopy).suffix(SHA256Digest.byteCount))
            
            // Check whether the digest has enough leading zero bytes (represented at the end in the bitcoin header format
            let leadingBytesData = secondRoundDigest.suffix(blockHeader.difficultyTarget)
            if let maxValue = leadingBytesData.max(), maxValue == 0 {
                // found a valid result. Set the previously defined vars resultNonce and resultHashHex to the values of the solution. Further hashing is stopped.
                resultNonce = n
                resultHashHex = Data(secondRoundDigest.reversed()).hexEncodedString()
                break;
            }
            
            noncesTried += 1
        }
        
        // kill the timer, as computations are over
        if let _ = timer {
            timer!.invalidate()
            timer = nil
        }
        
        // check whether we found a solution or not. Create a WorkResult Object for data tranmission to the server. Use ServerConnector to send Results to Server
        if let _ = resultNonce, let _ = resultHashHex {
            let workResult = WorkResult(jobId: miningJob.jobId, clientId: miningJob.clientId, solutionFound: true, nonce: resultNonce, blockHash: resultHashHex)
            ServerConnector.shared.sendMiningResponse(token: self.token!, workResult: workResult)
        } else {
            let workResult = WorkResult(jobId: miningJob.jobId, clientId: miningJob.clientId, solutionFound: false, nonce: nil, blockHash: nil)
            ServerConnector.shared.sendMiningResponse(token: self.token!, workResult: workResult)
        }
        
    }
 
    
    // MARK: ServerConnectorDelegate

    /**
     Is being called by the ServerConnector, when a new mining job has been received by the server
     */
    func miningJobResponse(response: MiningJob) {
        startMining(miningJob: response)
    }

    /**
     Is being called by the ServerConnector, when the result of a mining job has been successfully sent to the server
     */
    func jobResultResonse() {
        // request next mining job
        ServerConnector.shared.requestMiningJob(token: self.token!)
    }

    /**
     Is being called by the ServerConnector, when the App has successfully registered for a new clientId and token
     */
    func registrationResponse(response: RegisterResult) {
        // Add clientId and token to keystore
        let userIdAsString = String(response.clientId)
        let token = response.token.data(using: String.Encoding.utf8)!
        
        let query: [String: Any] = [kSecClass as String: kSecClassInternetPassword, kSecAttrAccount as String: userIdAsString, kSecAttrServer as String: server, kSecValueData as String: token]
        
        let status = SecItemAdd(query as CFDictionary, nil)
        guard status == errSecSuccess else {
            // show error message, when clientId / token could not be stored in KeyStore
            DispatchQueue.main.async {
                let alert = UIAlertController(title: "Error", message: "Could not add miner credentials to KeyStore.", preferredStyle: .alert)
                alert.addAction(UIAlertAction(title: "OK", style: .default, handler: nil))
                self.present(alert, animated: true)
            }
            return
        }
        
        // set the class variables for clientId and token to the values received by the Server
        self.clientId = response.clientId
        self.token = response.token
        
        // As we now have the client ID, we can update the Client Label in the Main View
        DispatchQueue.main.async {
            self.clientIDLabel.text = String(self.clientId!)
        }
        
        // now that we are registered, let's request a mining job
        ServerConnector.shared.requestMiningJob(token: self.token!)
    }

    /**
     Display an error from the ServerConnector
     */
    func serverError(text: String) {
        DispatchQueue.main.async {
            let alert = UIAlertController(title: "Error", message: text, preferredStyle: .alert)
            alert.addAction(UIAlertAction(title: "OK", style: .default, handler: nil))
            self.present(alert, animated: true)
        }
    }

    
}
