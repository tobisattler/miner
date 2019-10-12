//
//  MinerDataStruct.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 12.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import Foundation

struct MiningJob: Codable {
    let jobId: Int
    let clientId: Int
    let blockHeader: BlockHeader
}

struct BlockHeader: Codable {
    let version: Int32
    // Hex encoded as String
    let prevBlockHash: String
    // Hex encoded as String
    let merkleRoot: String
    let timestamp: Int32
    let difficultyTarget: Int32
    let nonce: Int
}
