//
//  ServerConnector.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 12.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import Foundation

class ServerConnector {
    
    private var observers = [ServerConnectorObserver]()
    
    func attachObserver(observer: ServerConnectorObserver) {
        observers.append(observer)
    }
    
    func notifyMiningJob(miningJob: MiningJob) {
        for observer in observers {
            observer.miningJobResponse(response: miningJob)
        }
    }
    
    func notifyJobResultResponse() {
        for observer in observers {
            observer.jobResultResonse()
        }
    }
    
    static let shared = ServerConnector()
    
    private init() {}
    
    func requestMiningJob() {
        let configuration = URLSessionConfiguration.default
        let session = URLSession(configuration: configuration)
        let url = URL(string: "https://mining.sattler.cool/v1/work")
        var request:URLRequest = URLRequest(url: url!)
        request.httpMethod = "GET"
        
        let dataTask = session.dataTask(with: request) { data,response,error in
            guard let httpResponse = response as? HTTPURLResponse, let receivedData = data
                else {
                    print("error: not a valid http response")
                    return
            }
            switch (httpResponse.statusCode) {
            case 200: //success response.
                let jsonDecoder = JSONDecoder()
                do {
                    let miningJob: MiningJob = try jsonDecoder.decode(MiningJob.self, from: receivedData)
                    self.notifyMiningJob(miningJob: miningJob)
                } catch let error {
                    // TODO handle error
                    print(error)
                }
                
                break
            case 400:
                break
            default:
                break
            }
        }
        dataTask.resume()
    }
    
    func sendMiningResponse(workResult: WorkResult) {
        let jsonEncoder = JSONEncoder()
        var data: Data?
        do {
            data = try jsonEncoder.encode(workResult)
        } catch let error {
            // TODO handle error
            print(error)
            return
        }
        
        let configuration = URLSessionConfiguration.default
        let session = URLSession(configuration: configuration)
        let url = URL(string: "https://mining.sattler.cool/v1/submit")
        var request:URLRequest = URLRequest(url: url!)
        request.httpMethod = "POST"
        request.httpBody = data!
        
        let dataTask = session.dataTask(with: request) { data,response,error in
            guard let httpResponse = response as? HTTPURLResponse
                else {
                    print("error: not a valid http response")
                    return
            }
            switch (httpResponse.statusCode) {
            case 200: //success response.
                
                self.notifyJobResultResponse()
                
                break
            case 400:
                break
            default:
                print("httpResponse.statusCode: \(httpResponse.statusCode) message: \(String(data: data!, encoding: .utf8)!)")
                break
            }
        }
        
        dataTask.resume()
    }
}
