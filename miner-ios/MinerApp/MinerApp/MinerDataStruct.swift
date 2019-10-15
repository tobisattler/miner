//
//  MinerDataStruct.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 12.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import Foundation

/**
 Struct for mining jobs, received by the server.
 */
struct MiningJob: Codable {
    let jobId: Int
    let clientId: Int
    let blockHeader: BlockHeader
    let startNonce: UInt32
    let endNonce: UInt32
}

/**
 Struct for BlockHeaders received by the server.
 */
struct BlockHeader: Codable {
    let version: UInt32
    // Hex encoded as String
    let prevBlockHash: String
    // Hex encoded as String
    let merkleRoot: String
    let timestamp: UInt32
    let nbits: UInt32
    let difficultyTarget: Int
}

/**
 Struct for computation results that are sent from the miner to the server.
 */
struct WorkResult: Codable {
    let jobId: Int
    let clientId: Int
    let solutionFound: Bool
    let nonce: UInt32?
    let blockHash: String?
}

/**
 Struct to represent the server response after a successful registration of a new client
 */
struct RegisterResult: Codable {
    let clientId: Int
    let token: String
}
