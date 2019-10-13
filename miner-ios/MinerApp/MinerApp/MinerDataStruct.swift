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
    let version: UInt32
    // Hex encoded as String
    let prevBlockHash: String
    // Hex encoded as String
    let merkleRoot: String
    let timestamp: UInt32
    let nbits: UInt32
    let difficultyTarget: Int
    let nonce: UInt32
}

struct WorkResult: Codable {
    let jobId: Int
    let clientId: Int
    let solutionFound: Bool
    let nonce: UInt32?
    let blockHash: String?
}
