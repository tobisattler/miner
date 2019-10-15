<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Miner Statistics</title>
    <link rel="stylesheet" href="statistics.css">
  </head>
  <body>

<?php
require_once 'external/Medoo.php';
require_once 'Config.php';

use Medoo\Medoo;

$database = new Medoo([
    "database_type" => "mysql",
    "database_name" => Config::DB_DATABASE,
    "server" => Config::DB_SERVER,
    "username" => Config::DB_USER,
    "password" => Config::DB_PASS
]);

$jobs = $database->select(Config::TABLE_JOBS, [
    "jobId [Int]",
    "clientId [Int]",
    "puzzleId [Int]",
    "startNonce [Int]",
    "endNonce [Int]",
], [
    "finished" => true,
    "ORDER" => ["jobId" => "DESC"],
    "LIMIT" => 5
]);

?>
<h1>Last submitted job results</h1>
	<span class="jobHeader">
    	<div class="jobId">Job ID</div>
    	<div class="clientId">Client ID</div>
    	<div class="puzzleId">Puzzle ID</div>
    	<div class="startNonce">Start Nonce</div>
    	<div class="endNonce">End Nonce</div>
    </span><br/>
<?php

foreach ($jobs as $job) {
?>
    <span class="job">
    	<div class="jobId"><?php echo $job["jobId"]?></div>
    	<div class="clientId"><?php echo $job["clientId"]?></div>
    	<div class="puzzleId"><?php echo $job["puzzleId"]?></div>
    	<div class="startNonce"><?php echo $job["startNonce"]?></div>
    	<div class="endNonce"><?php echo $job["endNonce"]?></div>
    </span><br/>
<?php
}
    
$solvedPuzzles = $database->select(Config::TABLE_SOLUTIONS, [
    "[>]".Config::TABLE_PUZZLES => "puzzleId"
], [
    Config::TABLE_SOLUTIONS.".clientId",
    Config::TABLE_SOLUTIONS.".puzzleId",
    Config::TABLE_SOLUTIONS.".nonce",
    Config::TABLE_SOLUTIONS.".blockHash",
    Config::TABLE_SOLUTIONS.".timestamp",
    Config::TABLE_PUZZLES.".bitcoinBlockId"
], [
    "ORDER" => ["clientId" => "DESC"],
    "LIMIT" => 5
]);

?>
<h1>Last submitted solutions for puzzles</h1>
<span class="solutionHeader">
	<div class="clientId">Client ID</div>
	<div class="puzzleId">Puzzle ID</div>
	<div class="bitcoinBlockId">Bitcoin Block</div>
	<div class="nonce">Nonce</div>
	<div class="blockHash">block hash</div>
	<div class="timestamp">Time of submission</div>
</span><br/>
<?php
foreach ($solvedPuzzles as $solution) {
?>
	<span class="solution">
		<div class="clientId"><?php echo $solution["clientId"]?></div>
		<div class="puzzleId"><?php echo $solution["puzzleId"]?></div>
		<div class="bitcoinBlockId"><a href="https://live.blockcypher.com/btc/block/<?php echo $solution["blockHash"] ?>/"><?php echo $solution["bitcoinBlockId"]?></a></div>
		<div class="nonce"><?php echo $solution["nonce"]?></div>
		<div class="blockHash"><?php echo $solution["blockHash"]?></div>
		<div class="timestamp"><?php echo $solution["timestamp"]?></div>
	</span><br/>
<?php
}

?>
</body>
</html>