//
//  ServerConnectorDelegate.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 15.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import Foundation

/**
 Interface for delegation of server responses
 */
protocol ServerConnectorDelegate: AnyObject {
    func miningJobResponse(response: MiningJob)
    func registrationResponse(response: RegisterResult)
    func jobResultResonse()
    func serverError(text: String)
}
