//
//  ViewController.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 12.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import UIKit
import CommonCrypto

class ViewController: UIViewController, ServerConnectorObserver {
    @IBOutlet weak var statusLabel: UILabel!
    

    override func viewDidLoad() {
        super.viewDidLoad()
        ServerConnector.shared.attachObserver(observer: self)
        // Do any additional setup after loading the view.
        ServerConnector.shared.requestMiningJob()
    }

    func miningJobResponse(response: MiningJob) {
        DispatchQueue.main.async {
            self.statusLabel.text = "previous block hash: \(response.blockHeader.prevBlockHash)"
        }
        startMining(blockHeader: response.blockHeader)
    }
    
    func startMining(blockHeader: BlockHeader){
        
    }
}

