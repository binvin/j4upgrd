<?php
echo " Running the script now!";

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "chillnew";

// debug file
$file = 'debug_log.txt';

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

mysqli_query($conn, "SET NAMES 'utf8mb4'");

// Query to retrieve fj remove data from the database
$sql = 'SELECT id, title, REPLACE(JSON_EXTRACT(params, \'$.id\'), \'\"\', \'\') AS \'aid\', REPLACE(JSON_EXTRACT(params, \'$.keywords\'), \'\"\', \'\') AS \'keywords\', REPLACE(JSON_EXTRACT(params, \'$.anyOrAll\'), \'\"\', \'\') AS \'anyOrAll\', REPLACE(REPLACE(REPLACE(JSON_EXTRACT(params, \'$.catid\'), \'\"\', \'\'), \'[\', \'\'), \']\', \'\') AS \'catid\', REPLACE(JSON_EXTRACT(params, \'$.matchAuthor\'), \'\"\', \'\') AS \'matchAuthor\', REPLACE(JSON_EXTRACT(params, \'$.matchAuthorAlias\'), \'\"\', \'\') AS \'matchAuthorAlias\' FROM j17_menu WHERE published = 1 AND client_id = 0 AND component_id = 10143;';
//$sql = 'SELECT  id,  title,   REPLACE(JSON_EXTRACT(params, \'$.id\'), \'\"\', \'\') AS \'aid\',   REPLACE(JSON_EXTRACT(params, \'$.keywords\'), \'\"\', \'\') AS \'keywords\',   REPLACE(JSON_EXTRACT(params, \'$.anyOrAll\'), \'\"\', \'\') AS \'anyOrAll\',   REPLACE(REPLACE(REPLACE(JSON_EXTRACT(params, \'$.catid\'), \'\"\', \'\'), \'[\', \'\'), \']\', \'\') AS \'catid\',   REPLACE(JSON_EXTRACT(params, \'$.matchAuthor\'), \'\"\', \'\') AS \'matchAuthor\',   REPLACE(JSON_EXTRACT(params, \'$.matchAuthorAlias\'), \'\"\', \'\') AS \'matchAuthorAlias\'  FROM   j17_menu_ORG WHERE   published = 1   AND client_id = 0   AND component_id = 10143 AND REPLACE(JSON_EXTRACT(params, \'$.id\'), \'\"\', \'\') = 0;';


// Execute the query
$result = $conn->query($sql);

// Check if the query was successful
if ($result) {
	// Loop through the result set and perform an action for each row
    while ($row = $result->fetch_assoc()) {
        // Access the columns of the current row using the associative array
        $Id = $row['id'];
        $title = $row['title'];
		$aId = $row['aid'];
        $keywords = $row['keywords'];
        $anyOrAll = $row['anyOrAll'];
		$catid = $row['catid'];
        $matchAuthor = $row['matchAuthor'];
        $anyOrAll = $row['anyOrAll'];
		$matchAuthorAlias = $row['matchAuthorAlias'];
		
		$keywords = html_entity_decode(preg_replace("/\\\u([0-9a-f]{4})/", "&#x\\1;", $keywords), ENT_NOQUOTES, 'UTF-8');
		
		if (!empty($title ) && isset($title ) && strlen($title) > 0) {
			// check if the title tag already exists.
			$countSql = 'SELECT count(*) AS row_count FROM j17_tags where title = "' . $title . '"';

			$nresult = $conn->query($countSql);
			if ($nresult) {
				$nrow = $nresult->fetch_assoc();
				
				$rowCount = $nrow['row_count'];
				
				// If count is 0 then the tag doesnt exist already. So create it.
				if ($rowCount == 0) {
					$ssql = 'SELECT MAX(rgt) AS ccid FROM j17_tags';
					$sresult = $conn->query($ssql);
					// Check if the query was successful
					if ($sresult) {
						$srow = $sresult->fetch_assoc();
						$ccid = $srow['ccid'];
						
						$string = str_replace(['?', '!', '&', "'", '(', ')', '-', '.', '/', '`'], '', $title);
						$string = str_replace(['  '], ' ', $string);
						$string = str_replace([' '], '-', $string);
						$string = str_replace(['--'], '-', $string);
						
						$lstring = strtolower($string);
						
						$isql = 'INSERT INTO j17_tags (parent_id, level, path, title, alias, published, access, created_user_id, rgt, lft, `language`) VALUES (1, 1,\'' . $lstring . '\', \'' . mysqli_real_escape_string($conn, $title) . '\', \'' . $lstring . '\', 1, 1, 42, (' . $ccid + 1 . '), (' . $ccid . '), "*" )';

						if ($conn->query($isql) === TRUE) {
							$newRowId = $conn->insert_id;
							echo "New row inserted with ID: " . $newRowId . "<br>";
							
							//Update the root tag with new rgt value.
							$utsql = "UPDATE j17_tags SET rgt = ? WHERE id = 1";
							$utstmt = $conn->prepare($utsql);
							$trgt = $ccid + 2;
							$utstmt->bind_param("i", $trgt);
							
							// Execute the update query
							if ($utstmt->execute()) {
								// Check if any rows were affected by the update
								$rowCount = $utstmt->affected_rows;
								if ($rowCount > 0) {
									echo "Root Tag Update successful. $rowCount row(s) updated.";
								} else {
									echo "Root Tag update error. SKIPPING!";
									// debug file
									$txt = "Root tag was not updated while updating for the menu : " . $menuId;
									file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
									// Close the statement and connection
									$utstmt->close();
									continue;
								}
							} else {
								echo "Error executing root tag update query: " . $conn->error;
								// debug file
								$txt = "Error executing root tag update statement for the menu : " . $menuId . " so, SKIPPING!";
								file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
								// Close the statement and connection
								$utstmt->close();
								continue;
							}
							
							// Close the statement and connection
							$utstmt->close();
						} else {
							echo "Error in INSERT Query: " . $isql . "<br>" . $conn->error;
							$txt = "Error in INSERT Query: " . $isql . " for the menu  : " . $Id . " and the title is " . $title . " so, SKIPPING THE menu!";
							file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
							continue;
						}
					}else {
						echo "Error querying j17_tags table (max rgt) id for the menu  : " . $title . "<br>";
						$txt = "Error querying j17_tags table (max rgt) id for the menu  : " . $title . " and the menu id is " . $Id . " so, SKIPPING THE menu!";
						file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
						continue;
					}
				}
				
				// Fetch the tag id.
				$sesql = 'SELECT id FROM j17_tags where title = "' . $title . '"';
				$seresult = $conn->query($sesql);
				// Check if the query was successful
				if ($seresult) {
					$serow = $seresult->fetch_assoc();
					$tagId = $serow['id'];
				}else {
					echo "Error querying j17_tags table (tag id) id for the menu  : " . $title . "<br>";
					$txt = "Error querying j17_tags table (tag id) id for the menu  : " . $title . " and the menu id is " . $Id . " so, SKIPPING THE menu!";
					file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
					continue;
				}
			}else {
				echo "Error querying j17_tags table id for the menu  : " . $title . "<br>";
				$txt = "Error querying (count) j17_tags table id for the menu  : " . $title . " and the menu id is " . $Id . " so, SKIPPING THE ARTICLE!";
				file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
				continue;
			}
		} else {
			echo "Title is empty for the menu : " . $Id . " - " . $title . ". <br>";
			// debug file
			$txt = "Title is empty for the menu : " . $Id . " - " . $title . " so, SKIPPING!";
			file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
			continue;
		}
		
		// If Intro article exists, fetch and update the tags table.
		if ($aId > 0){
			$selsql = 'SELECT introtext, created_by, created_by_alias, metakey, metadesc FROM j17_content WHERE ID = ' . $aId;
			
			$selresult = $conn->query($selsql);
			// Check if the query was successful
			if ($selresult) {
				$selrow = $selresult->fetch_assoc();
				$intro = $selrow['introtext'];
				$thisAlias = $selrow['created_by_alias'];
				$thisAuthor = $selrow['created_by'];
				$keywords = $selrow['metakey'];
				$keywords = html_entity_decode(preg_replace("/\\\u([0-9a-f]{4})/", "&#x\\1;", $keywords), ENT_NOQUOTES, 'UTF-8');
			}else {
				echo "Not able to fetch intro article for the menu : " . $Id . " - " . $title . " - article id : " . $aId . " <br>";
				// debug file
				$txt = "ot able to fetch intro article for the menu : " . $Id . " - " . $title . " - article id : " . $aId . " so, SKIPPING!";
				file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
				continue;
			}
			
			// Add metadata info to the tag from the intro article.
			// If introtext has jumi, ignore it, else add it to the tag.
			if(strpos(strtoupper($intro), "JUMI") !== false){
				$udsql = 'UPDATE j17_tags SET metadesc = ?, metakey = ? WHERE id = ?';
				$ustmt = $conn->prepare($udsql);
				$ustmt->bind_param("ssi", $selrow['metadesc'], $selrow['metakey'], $tagId);
			}else {
				$udsql = 'UPDATE j17_tags SET description = ?, metadesc = ?, metakey = ? WHERE id = ?';
				$ustmt = $conn->prepare($udsql);
				$ustmt->bind_param("sssi", $intro, $selrow['metadesc'], $selrow['metakey'], $tagId);
			}
		
			// Execute the update query
			if ($ustmt->execute()) {
				$rowCount = $ustmt->affected_rows;
				if ($rowCount > 0) {
					echo "Update successful. $rowCount row(s) updated.";
					// debug file
					$txt = "metadata SUCCESSfully updated for the tag : " . $tagId;
					file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
				} else {
					echo "No rows updated. The ID may not exist in the TAG table. CONTINUING!";
					// debug file
					$txt = "metadata not updated for the tag : " . $tagId . " CONTINUING!";
					file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
				}
			}else {
				echo "Error executing TAG update query: " . $conn->error;
				// debug file
				$txt = "Error executing update statement for the tag : " . $tagId. " CONTINUING!";
				file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
			}
			// Close the statement and connection
			$ustmt->close();			
		}else{
			$matchAuthor = 0;
			$matchAuthorAlias = 0;
			$thisAlias = '';
			$thisAuthor = '';
		}

		// Build query based on keywords and author conditions.
		if (($keywords) || 	// do the query if there are keywords
		($matchAuthor) || 	// or if the author match is on
		(($matchAuthorAlias) && ($thisAlias)))	// or if the alias match is on and an alias
		{

			// explode the meta keys on a comma
			$keys = explode(',', $keywords);
			$likes = array ();

			// assemble any non-blank word(s)
			foreach ($keys as $key){
				$key = trim($key);
				if ($key) {
					// surround with commas so first and last items have surrounding commas
					$likes[] = ',' . mysqli_real_escape_string($conn, $key) . ',';
				}
			}

			// set connector to OR or AND based on parameter
			$sqlConnector =  ($anyOrAll == 'any') ? ' OR ' : ' AND ';

			if (($likes) && ($anyOrAll != 'exact')) {
				$keywordSelection = ' CONCAT(",", REPLACE(a.metakey,", ",","),",") LIKE "%'.
				implode('%"' . $sqlConnector . 'CONCAT(",", REPLACE(a.metakey,", ",","),",") LIKE "%', $likes).'%"';
			} else if (($likes) && ($anyOrAll == 'exact')) {
				$keywordSelection = ' UPPER(a.metakey) = "' . strtoupper($metakey) . '" ';
			} else { // in this case we are only going to match on author or alias, so we put a harmless false selection here
				$keywordSelection = ' 1 = 2 '; // just as a placeholder (so our AND's and OR's still work)
			}

			if ($matchAuthor) {
				$matchAuthorCondition = $sqlConnector . 'a.created_by = ' . '"' . mysqli_real_escape_string($conn, $thisAuthor) . '" ';
			}else {
				$matchAuthorCondition = ' ';
			}

			if (($matchAuthorAlias) && ($thisAlias)) {
				$matchAuthorAliasCondition = $sqlConnector . 'UPPER(a.created_by_alias) = ' . '"' . mysqli_real_escape_string($conn, strtoupper($thisAlias)) . '" ';
			}else {
				$matchAuthorAliasCondition = ' ';
			}

			// select other items based on the metakey field 'like' the keys found
			$query = 'SELECT ID FROM j17_content AS a WHERE a.state = 1 ';
			$query = $query  . ' AND a.catid in (' . trim($catid) . ') ';
			$query = $query  . ' AND ( ' ;
			$query = $query  . $keywordSelection;
			$query = $query  . ($matchAuthor ? $matchAuthorCondition : '' ); // author match part of OR clause
			$query = $query  . ($matchAuthorAlias ? $matchAuthorAliasCondition : ''); // author alias part of OR clause
			$query = $query  . ' )';
			
			$insql = 'INSERT INTO fj_remove (menuid, tagid, query) VALUES (' . $Id . ', '. $tagId . ', \'' . mysqli_real_escape_string($conn, $query) . '\' )';

			if ($conn->query($insql) === TRUE) {
				$nwRowId = $conn->insert_id;
				echo "New row inserted with ID: " . $nwRowId . "<br>";
			} else {
				echo "Error in INSERT Query: " . $insql . "<br>" . $conn->error;
				$txt = "Error in INSERT Query: " . $insql . " for the menu  : " . $Id . " and the title is " . $title . " so, SKIPPING THE menu!";
				file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
				continue;
			}
		}else{
			$query = '';
			echo "Error : No query condition defined";
			$txt = "Error : No query condition defined for menu - " . $Id . " - " . $title . ". so, SKIPPING THE menu!";
			file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
			continue;
		}
	} //while loop
} else {
    // Query execution failed
    echo "Error : " . $sql . "<br>" . $conn->error;
	$txt = "Error : " . $sql;
	file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
}

echo " Script completed!";
?>
