//
//  KeychainStruct.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 15.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import Foundation

struct Client {
    var clientId: Int
    var token: String
}

enum KeychainError: Error {
    case noPassword
    case unexpectedPasswordData
    case unhandledError(status: OSStatus)
}
