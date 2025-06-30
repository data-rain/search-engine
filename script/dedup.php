<?php

$host = 'localhost';
$user = 'esmaeill';
$pass = '15031374';
$dbname = 'datarain';

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_errno) {
    die("[ERROR] Failed to connect to database: " . $mysqli->connect_error . PHP_EOL);
}

echo "[START] Database connection established." . PHP_EOL;

// Step 1: Create new table
echo "[1/3] Creating new table enamadv3_urls_new..." . PHP_EOL;
if (!$mysqli->query("DROP TABLE IF EXISTS enamadv3_urls_new")) {
    die("[ERROR] Failed to drop existing enamadv3_urls_new table: " . $mysqli->error . PHP_EOL);
}
if (!$mysqli->query("CREATE TABLE enamadv3_urls_new LIKE enamadv3_urls")) {
    die("[ERROR] Failed to create enamadv3_urls_new table: " . $mysqli->error . PHP_EOL);
}
echo "[✔] New table enamadv3_urls_new created successfully." . PHP_EOL;

// Step 2: Insert unique records in batches
$batchSize = 10000;
$offset = 0;
$totalInserted = 0;

while (true) {
    echo "[2/3] Selecting unique records from offset $offset..." . PHP_EOL;

    $result = $mysqli->query("
        SELECT MIN(id) as id 
        FROM enamadv3_urls 
        GROUP BY url
        LIMIT $batchSize OFFSET $offset
    ");

    if (!$result) {
        die("[ERROR] Failed to select records: " . $mysqli->error . PHP_EOL);
    }

    if ($result->num_rows === 0) {
        echo "[✔] All records processed successfully." . PHP_EOL;
        break;
    }

    while ($row = $result->fetch_assoc()) {
        $id = (int)$row['id'];

        if (!$mysqli->query("
            INSERT IGNORE INTO enamadv3_urls_new
            SELECT * FROM enamadv3_urls WHERE id=$id
        ")) {
            die("[ERROR] Failed to insert record id=$id: " . $mysqli->error . PHP_EOL);
        }
        $totalInserted++;
        //echo "  → Inserted record id=$id." . PHP_EOL;
    }

    $offset += $batchSize;
}

echo "[✔] Total inserted records into enamadv3_urls_new: $totalInserted" . PHP_EOL;

// Step 3: Display swap instructions
echo PHP_EOL;
echo "[3/3] ✅ Unique records have been inserted successfully." . PHP_EOL;
echo "⚠️ To replace the old table with the new one, run these SQL commands manually:" . PHP_EOL;
echo "  RENAME TABLE enamadv3_urls TO enamadv3_urls_old;" . PHP_EOL;
echo "  RENAME TABLE enamadv3_urls_new TO enamadv3_urls;" . PHP_EOL;

$mysqli->close();
echo "[END] Database connection closed." . PHP_EOL;
?>
