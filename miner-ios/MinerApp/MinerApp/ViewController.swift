//
//  ViewController.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 12.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import UIKit
import CryptoKit

class ViewController: UIViewController, ServerConnectorObserver {
    @IBOutlet weak var statusLabel: UILabel!
    

    override func viewDidLoad() {
        super.viewDidLoad()
        ServerConnector.shared.attachObserver(observer: self)
        // Do any additional setup after loading the view.
        ServerConnector.shared.requestMiningJob()
    }

    func miningJobResponse(response: MiningJob) {
        /*DispatchQueue.main.async {
            self.statusLabel.text = "previous block hash: \(response.blockHeader.prevBlockHash)"
        }*/
        startMining(miningJob: response)
    }
    
    func jobResultResonse() {
        // request next mining job
        ServerConnector.shared.requestMiningJob()
    }
    
    /**
     starts the mining task.
     See also:
     [Internal byte order of Bitcoin header](https://bitcoin.org/en/glossary/internal-byte-order)
     */
    func startMining(miningJob: MiningJob){
        let blockHeader = miningJob.blockHeader
        DispatchQueue.main.async {
            self.statusLabel.text = "Start Mining Job. Merkle Root: \(blockHeader.merkleRoot)"
        }
        
        // Encode UInt32 values into data object
        let versionData = blockHeader.version.data()
        let timeStampData = blockHeader.timestamp.data()
        let nbitsData = blockHeader.nbits.data()
        
        // Encode Hex String for prevBlockHash and merkleRoot as data object. The byte order is being reversed to match the internal byte order of the Bitcoin header.
        let prevBlockHashData = Data(Data(blockHeader.prevBlockHash.hexDecodedData()).reversed())
        let merkleRootData = Data(Data(blockHeader.merkleRoot.hexDecodedData()).reversed())
        
        // Create the Bitcoin block header
        let headerData = NSMutableData()
        headerData.append(versionData)
        headerData.append(prevBlockHashData)
        headerData.append(merkleRootData)
        headerData.append(timeStampData)
        headerData.append(nbitsData)
        //headerData.append(nonceData)
        
        var resultNonce: UInt32?
        var resultHashHex: String?
        for n in miningJob.startNonce...miningJob.endNonce {
            let headerCopy = NSMutableData()
            headerCopy.setData(headerData as Data)
            headerCopy.append(n.data())
            
            // Perform two rounds of sha265 hashes, using Apple CryptoKit
            let secondRoundDigest = SHA256.hash(data: SHA256.hash(data: headerCopy).suffix(SHA256Digest.byteCount))
            
            // TODO: REMOVE DEBUGGING
            //let currentHex = Data(secondRoundDigest.reversed()).hexEncodedString()
            
            // Check whether the digest has enough leading zero bytes (represented at the end in the bitcoin header format
            let leadingBytesData = secondRoundDigest.suffix(blockHeader.difficultyTarget)
            if let maxValue = leadingBytesData.max(), maxValue == 0 {
                resultNonce = n
                resultHashHex = Data(secondRoundDigest.reversed()).hexEncodedString()
                break;
            }
            
            //let hashStringHex = Data(secondRoundDigest.reversed()).hexEncodedString()
        }
        
        if let _ = resultNonce, let _ = resultHashHex {
            DispatchQueue.main.async {
                self.statusLabel.text = "hash found: \(resultHashHex!), nonce: \(resultNonce!)"
            }
            
            let workResult = WorkResult(jobId: miningJob.jobId, clientId: miningJob.clientId, solutionFound: true, nonce: resultNonce, blockHash: resultHashHex)
            ServerConnector.shared.sendMiningResponse(workResult: workResult)
        } else {
            DispatchQueue.main.async {
                self.statusLabel.text = "no hash found for current work job."
            }
            
            let workResult = WorkResult(jobId: miningJob.jobId, clientId: miningJob.clientId, solutionFound: false, nonce: nil, blockHash: nil)
            ServerConnector.shared.sendMiningResponse(workResult: workResult)
        }
        
        
        DispatchQueue.main.async {
            //self.statusLabel.text = "hash: \(hashStringHex)"
        }
    }
    
}

extension String {
  /// A data representation of the hexadecimal bytes in this string.
  func hexDecodedData() -> Data {
    // Get the UTF8 characters of this string
    let chars = Array(utf8)

    // Keep the bytes in an UInt8 array and later convert it to Data
    var bytes = [UInt8]()
    bytes.reserveCapacity(count / 2)

    // It is a lot faster to use a lookup map instead of strtoul
    let map: [UInt8] = [
      0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, // 01234567
      0x08, 0x09, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, // 89:;<=>?
      0x00, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f, 0x00, // @ABCDEFG
      0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00  // HIJKLMNO
    ]

    // Grab two characters at a time, map them and turn it into a byte
    for i in stride(from: 0, to: count, by: 2) {
      let index1 = Int(chars[i] & 0x1F ^ 0x10)
      let index2 = Int(chars[i + 1] & 0x1F ^ 0x10)
      bytes.append(map[index1] << 4 | map[index2])
    }

    return Data(bytes)
  }
}

extension Data {
  /// A hexadecimal string representation of the bytes.
  func hexEncodedString() -> String {
    let hexDigits = Array("0123456789abcdef".utf16)
    var hexChars = [UTF16.CodeUnit]()
    hexChars.reserveCapacity(count * 2)

    for byte in self {
      let (index1, index2) = Int(byte).quotientAndRemainder(dividingBy: 16)
      hexChars.append(hexDigits[index1])
      hexChars.append(hexDigits[index2])
    }

    return String(utf16CodeUnits: hexChars, count: hexChars.count)
  }
}

extension UInt32 {
    func dataLittleEndian() -> Data {
        return Data([
            UInt8((self & 0xFF000000) >> 24),
            UInt8((self & 0x00FF0000) >> 16),
            UInt8((self & 0x0000FF00) >> 8),
            UInt8((self & 0x000000FF))
        ])
    }
    
    func uint8Array() -> [UInt8] {
        return [
            UInt8((self & 0x000000FF)),
            UInt8((self & 0x0000FF00) >> 8),
            UInt8((self & 0x00FF0000) >> 16),
            UInt8((self & 0xFF000000) >> 24)
        ]
    }
    
    func data() -> Data {
        return Data([
            UInt8((self & 0x000000FF)),
            UInt8((self & 0x0000FF00) >> 8),
            UInt8((self & 0x00FF0000) >> 16),
            UInt8((self & 0xFF000000) >> 24)
        ])
    }
}
