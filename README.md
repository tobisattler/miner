# Miner

## Abstact

The implemented solution consists of:

- MinerApp: iOS Mining App
- miner-server: php server with usage of MariaDB as database

In the solution, the server generates cryptographic puzzles using data from the Bitcoin blockchain. The data is being retrieved using the public blockchain API of https://www.blockcypher.com/.

The generated puzzles contain all the data fields from the original Bitcoin block header, except for the nonce. The nonce field is being altered by the mining clients until a valid solution for the puzzle is found (or all possible nonce combinations have been tried out unsuccessfully).

The server uses the nonce value to split up the work between all clients. Clients requesting for work will retrieve a job from the server. In the job, a range of nonces is defined, that the client has to try out (4,194,304 in the default configuration). If the resulting hash value has enough leading zeros, the puzzle is considered as solved and the client sends the solution back to the server. The server then creates a new puzzle when the next request for work is received.

The clients use an API token to authenticate themselves to the server. Clients, that don't yet have a clientId and token stored in their KeyChain will send a register request to the server. The server will then create new credentials and send them back to the client, who will store them in the KeyChain and use them for all subsequent server requests.

The php server software has been deployed to a rented server at hetzner. It is running on an Apache webserver within a Linux container which is accessible through a reverse proxy. The communication between mining clients and reverse proxy server is secured using https. The used certificate was issued by Let's Encrypt.

Here are the server endpoints for API calls:

- https://mining.sattler.cool/v1/register
- https://mining.sattler.cool/v1/work
- https://mining.sattler.cool/v1/submit

## miner-server

The API endpoints of the server are preceeded by v1. This would make it easier to migrate to a new version of the API in the future.

For database communication, the framework Medoo is being used ( https://medoo.in/ ).

### Client registration

Server endpoint: https://mining.sattler.cool/v1/register

Upon receiving a request for new credentials, the server generates an API token, using the microtime() function of php combined with a random number. Those two components are combined and hashed using sha256. The resulting hash is then checked against all previously issued tokens to avoid a hash collision (which should in theory never happen). The token is then stored in the database and returned to the client together with the insert id in the MariaDB database, which is being used as clientId.

### Work requests

Server endpoint: https://mining.sattler.cool/v1/work

When receiving a request for new work data, the server will retrieve the latest puzzle from its database. The following checks are taking place, before a new job is being created and sent to the client:

**Has the puzzle already been solved?**

If there is a solution stored in the solutions database table, the latest puzzle is considered as being solved. The server will then contact the blockcypher API to retrieve the last block in the Bitcoin Blockchain. The block header data is then being used to create a new puzzle (except for the nonce field). Additionally, the amount of leading zero bytes needed to solve the puzzle is calculated.

If the server has problems contacting the blockcypher API, it will randomly "recycle" a previously used puzzle. Recycling means, that the data from the old puzzle is being copied into a puzzle with a new, unique id.

**Does the client still have an open job?**

If the client requesting for work still has an unfinished job ("finished" value of the job is set to false in the database), that job will be resent to the client.

**Is there still a range of nonces that has not been issued to clients?**

The server will retrieve the highest nonce value that has already been issued to any client as part of a job. If the highest possible nonce (4,294,967,295) has not been given out for calculation yet, a new mining job will be created with a maximum range of 4,194,304 nonces, that the client needs to calculate (e.g. the issued range will start directly above the previously highest issued range). The value 4,194,304 is defined in the Config.php file as NONCES_PER_JOB. It has been set to this value to have a total of 1,024 different jobs per puzzle.

If the highest possible nonce value has already been issued as part of a job, the server will check whether there are still any unfinished jobs for the current puzzle (i.e. jobs, where the responsible client has not yet sent results to the server). If that is the case, the server will copy a randomly selected unfinished job and send it to the client. If there are no unfinished jobs, the puzzle is considered as unsolvable. In reality, this should not happen, as we are using real Bitcoin Block data. But if it happens nevertheless, the server will create a new puzzle, in the way described before.

### Submission of work results

Server endpoint: https://mining.sattler.cool/v1/submit

Upon receiving result data of a client, the server will mark the corresponding job as finished in the database. The server will then check whether the client found a solution (nonce value and resulting hash transmitted within the data). If that is the case, the solution is stored in the database (the next work request will then result in creating a new puzzle). If the result data does not contain a solution for the puzzle, the server will mark any other jobs in the database with the same nonce-range as finished (if any exist). This is needed to prevent the server from reissuing those jobs again if the highest possible nonce is already part of an issued job.

### Database structure

The structure of the database has been dumped into a .sql file within the database_scheme folder.

## MinerApp

### Registration

The iOS MinerApp will try to retrieve its clientId and API token from the KeyChain after starting. In case that no credentials can be retrieved from there, a new request will be sent to the server, using the "register" endpoint.

After receiving the newly generated credentials (clientId and token) from the server, the App will store them in the KeyChain.

### Retrieving work

After successfully having retrieved the API token, the MinerApp will ask for a new job, using the "work" endpoint of the server. The received work will then be used to perform the cryptographic calculation.

### Header calculation

Before the hashes can be calculated, the header data (except for the nonce) is being transferred in the format as needed for the Bitcoin header (for more information, see  https://bitcoin.org/de/entwickler-referenzen#block-headers ). For example, the order of the hex values of the previous block header hash and the merkle root have to be reversed.

After that, the App will loop through all nonce values between the startNonce value and the endNonce value as provided by the server in the job description. In each iteration of the loop, a hard copy of the previously created scaffolding of the Block Header is created. The current nonce value is then being appended into the header data. The complete header is then hashed twice, using sha256. The hash calculations are being performed using the Apple CryptoKit. This CryptoKit is being used as it has optimizations for the hardware used in iOS devices - according to Apple.

While the calculation loop is running, a counter is running increasing the "Job Duration" timer in the UI. This will give the user some feedback, that the App has not crashed. Also in the same step, the average generated hashes per second are calculated and displayed in the UI.

The loop will be interrupted whenever the threshold of leading zeros has been reached and the puzzle is thus considered as solved.

### Work result

When a solution of the current puzzle has been found, the client will send a data package containing jobId, clientId, a boolean "solutionFound", the nonce used in the solution and the resulting "blockHash" to the server's submit endpoint. If the App finished without finding a solution, the same data package will be sent to the server, setting "solutionFound" to false and leaving out the "nonce" and "blockHash" fields.

After submitting the work results, the App will ask for the next job, using the server's "work" endpoint.