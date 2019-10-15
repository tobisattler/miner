//
//  ServerConnector.swift
//  MinerApp
//
//  Created by Sattler Tobias, EE-252 on 12.10.19.
//  Copyright © 2019 Sattler Tobias, EE-62. All rights reserved.
//

import Foundation

/**
 ServerConnector will handle the communication between the miner App and the server.
 It implements the singleton design pattern, so that there is only one central object for all server communication.
 Also, it implements the Observable design pattern, so that the requesting parts of the App will get notified after the server responded.
 */
class ServerConnector {
    
    /*// holds a list of Observers
    private var observers = [ServerConnectorObserver]()
    
    /**
     Function that observers call in order to register themselves to get notified when there is a result from the server.
     */
    func attachObserver(observer: ServerConnectorObserver) {
        observers.append(observer)
    }*/
    
    /**
     Notify all observers of a new MiningJob received by the server.
     */
    /*func notifyMiningJob(miningJob: MiningJob) {
        for observer in observers {
            observer.miningJobResponse(response: miningJob)
        }
    }
    
    /**
     Notify all observers that the job result has been successfully sent to the server
     */
    func notifyJobResultResponse() {
        for observer in observers {
            observer.jobResultResonse()
        }
    }
    
    /**
     Notify all observers of the successful creation of a new client and hands over the credentials
     */
    func notifyRegistrationResponse(registerResult: RegisterResult) {
        for observer in observers {
            observer.registrationResponse(response: registerResult)
        }
    }*/
    
    // holds the actual instance of the ServerConnector
    static let shared = ServerConnector()
    
    private init() {}
    
    weak var delegate: ServerConnectorDelegate?
    
    static var serverUrl = "https://mining.sattler.cool"
    /**
     Establish a Server Connection in order to request a new MiningJob
     */
    func requestMiningJob(token: String) {
        let configuration = URLSessionConfiguration.default
        let session = URLSession(configuration: configuration)
        let url = URL(string: "\(ServerConnector.serverUrl)/v1/work?token=\(token)")
        var request:URLRequest = URLRequest(url: url!)
        request.httpMethod = "GET"
        
        let dataTask = session.dataTask(with: request) { data,response,error in
            guard let httpResponse = response as? HTTPURLResponse, let receivedData = data
                else {
                    ServerConnector.shared.delegate?.serverError(text: "HTTP response by the server is invalid.")
                    return
            }
            switch (httpResponse.statusCode) {
            case 200: //success response.
                let jsonDecoder = JSONDecoder()
                do {
                    let miningJob: MiningJob = try jsonDecoder.decode(MiningJob.self, from: receivedData)
                    ServerConnector.shared.delegate?.miningJobResponse(response: miningJob)
                } catch {
                    ServerConnector.shared.delegate?.serverError(text: "Error parsing Mining Job from the server")
                }
                
                break
            default:
                ServerConnector.shared.delegate?.serverError(text: "The server responded an error \(httpResponse.statusCode): \(String(data: data!, encoding: .utf8)!)")
                break
            }
        }
        dataTask.resume()
    }
    
    /**
     Establish a server connection in order to send a mining result to the server
     */
    func sendMiningResponse(token: String, workResult: WorkResult) {
        let jsonEncoder = JSONEncoder()
        var data: Data?
        do {
            data = try jsonEncoder.encode(workResult)
        } catch {
            ServerConnector.shared.delegate?.serverError(text: "Error creating the work result.")
            return
        }
        
        let configuration = URLSessionConfiguration.default
        let session = URLSession(configuration: configuration)
        let url = URL(string: "\(ServerConnector.serverUrl)/v1/submit?token=\(token)")
        var request:URLRequest = URLRequest(url: url!)
        request.httpMethod = "POST"
        request.httpBody = data!
        
        let dataTask = session.dataTask(with: request) { data,response,error in
            guard let httpResponse = response as? HTTPURLResponse
                else {
                    ServerConnector.shared.delegate?.serverError(text: "HTTP response by the server is invalid.")
                    return
            }
            switch (httpResponse.statusCode) {
            case 200: //success response.
                ServerConnector.shared.delegate?.jobResultResonse()
                
                break
            default:
                ServerConnector.shared.delegate?.serverError(text: "The server responded an error \(httpResponse.statusCode): \(String(data: data!, encoding: .utf8)!)")
                break
            }
        }
        
        dataTask.resume()
    }
    
    /**
     Sends a request to create new credentials for this client
     */
    func registerClient() {
        let configuration = URLSessionConfiguration.default
        let session = URLSession(configuration: configuration)
        let url = URL(string: "\(ServerConnector.serverUrl)/v1/register")
        var request:URLRequest = URLRequest(url: url!)
        request.httpMethod = "GET"
        
        let dataTask = session.dataTask(with: request) { data,response,error in
            guard let httpResponse = response as? HTTPURLResponse, let receivedData = data
                else {
                    ServerConnector.shared.delegate?.serverError(text: "HTTP response by the server is invalid.")
                    return
            }
            switch (httpResponse.statusCode) {
            case 200: //success response.
                let jsonDecoder = JSONDecoder()
                do {
                    let clientDetails: RegisterResult = try jsonDecoder.decode(RegisterResult.self, from: receivedData)
                    ServerConnector.shared.delegate?.registrationResponse(response: clientDetails)
                } catch {
                    ServerConnector.shared.delegate?.serverError(text: "Registration response from the server could not be decoded.")
                }
                
                break
            default:
                ServerConnector.shared.delegate?.serverError(text: "The server responded an error \(httpResponse.statusCode): \(String(data: data!, encoding: .utf8)!)")
                break
            }
        }
        dataTask.resume()
    }
}
