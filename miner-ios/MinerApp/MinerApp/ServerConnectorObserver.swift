//
//  ServerConnectorObserver.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 12.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import Foundation

protocol ServerConnectorObserver {
    func miningJobResponse(response: MiningJob)
    func jobResultResonse()
}
