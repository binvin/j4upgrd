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
$sql = "SELECT menuid, tagid, query FROM fj_remove where flag = 0";

// Execute the query
$result = $conn->query($sql);

// Check if the query was successful
if ($result) {
	$cnt = 0;
    // Loop through the result set and perform an action for each row
    while ($row = $result->fetch_assoc()) {
        // Access the columns of the current row using the associative array
        $menuId = $row['menuid'];
        $varQuery = $row['query'];
		$tagId = $row['tagid'];
		
		// Nested Query: To add the tags to the articles.
		if (!empty($varQuery ) && isset($varQuery ) && strlen($varQuery ) > 0) {
			// Execute the saved query
			$nestedResult = $conn->query($varQuery);

			// Check if the query was successful
			if ($nestedResult) {
                // Loop through the result set of the query and perform action
				while ($nestedRow = $nestedResult->fetch_assoc()) {
                    $aID = $nestedRow['ID'];

					// echo "Article ID : " . $aID . "<br>";

					if ($aID > 0) {
						// Run the COUNT statement to check if the row exists in the UCM table.
						$countSql = "SELECT COUNT(*) AS row_count FROM j17_ucm_content WHERE core_content_item_id = " . $aID . ' AND core_type_alias = "com_content.article" AND core_state = 1 AND core_type_id = 1';

						$nresult = $conn->query($countSql);
						if ($nresult) {
							$nrow = $nresult->fetch_assoc();

							$rowCount = $nrow['row_count'];

							if ($rowCount == 0) {								
								$isql = 'INSERT INTO j17_ucm_content (core_type_alias, core_title, core_alias, core_metadata, core_created_by_alias, core_language, core_content_item_id, core_catid, core_xreference, core_type_id, core_state, core_created_time ) SELECT "com_content.article", title, alias, metadata, created_by_alias, `language`, id, catid, "", 1, 1, created  from j17_content where id = ' .  $aID;
								
								// debug file
								// file_put_contents($file, $isql.PHP_EOL, FILE_APPEND);
								
								if ($conn->query($isql) === TRUE) {
									$newRowId = $conn->insert_id;
									echo "New row inserted into UCM table with ID: " . $newRowId . "<br>";
									
									$ibsql = 'INSERT INTO j17_ucm_base (ucm_id, ucm_item_id, ucm_type_id, ucm_language_id) SELECT core_content_id, core_content_item_id, 1, 0 from j17_ucm_content where core_content_item_id = ' .  $aID;
									if ($conn->query($ibsql) === TRUE) {
										$newRowId = $conn->insert_id;
										echo "New row inserted into UCM Base table with ID: " . $newRowId . "<br>";
									}else{
										echo "Error in UCM Base INSERT Query: " . $ibsql . "<br>" . $conn->error;
										$txt = "Error in UCM Base INSERT Query: " . $ibsql . " for the article  : " . $aID . " and the menu id is " . $menuId . " so, SKIPPING THE ARTICLE!";
										file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
										continue;
									}									
								} else {
									echo "Error in UCM INSERT Query: " . $isql . "<br>" . $conn->error;
									$txt = "Error in UCM INSERT Query: " . $isql . " for the article  : " . $aID . " and the menu id is " . $menuId . " so, SKIPPING THE ARTICLE!";
									file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
									continue;
								}
								
							}
							
							$ccid = 0;
							$isql = '';
							
							$ssql = 'SELECT max(core_content_id) AS ccid FROM j17_ucm_content where core_content_item_id = ' . $aID . ' and core_type_alias = "com_content.article"';
							$sresult = $conn->query($ssql);
							// Check if the query was successful
							if ($sresult) {
								while ($srow = $sresult->fetch_assoc()) {
									// Access the columns of the current row using the associative array
									$ccid = $srow['ccid'];
								}
							}else {
								echo "Error fetching UCM table id for the article  : " . $aID . "<br>";
								$txt = "Error fetching UCM table id for the article  : " . $aID . " and the menu id is " . $menuId . " so, SKIPPING THE ARTICLE!";
								file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
								continue;
							}

							if ($ccid > 0) {
								$isql = 'INSERT INTO j17_contentitem_tag_map (tag_id, type_alias, content_item_id, core_content_id, type_id) VALUES (' . $tagId . ', "com_content.article", ' . $aID . ', ' . $ccid . ', 1) ON DUPLICATE KEY UPDATE tag_date = CURRENT_TIMESTAMP(), core_content_id = ' . $ccid . ', type_alias = "com_content.article";';
								// debug file
								//file_put_contents($file, $isql.PHP_EOL, FILE_APPEND);

								if ($conn->query($isql) === TRUE) {
									$newRowId = $conn->insert_id;
									echo "New contentitem_tag_map row inserted with ID: " . $newRowId . "<br>";
									$txt = "SUCCESS  : " . $menuId . " - " . $tagId . " - " . $aID;
									$cnt = $cnt + 1;
									file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
								} else {
									echo "Error in contentitem_tag_map INSERT Query: " . $isql . "<br>" . $conn->error;
									$txt = " Error in contentitem_tag_map INSERT Query: " . $isql . " for the article id " . $aID . " and the menu id is " . $menuId . " so, SKIPPING THE ARTICLE!";
									file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
									continue;
								}
							} else {
								echo "UCM table id is 0 for the article : " . $aID . "<br>";
								$txt = " UCM table id is 0 for the article  : " . $aID . " and the menu id is " . $menuId . " so, SKIPPING THE ARTICLE!";
								file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
								continue;
							}
							
						}else {
							echo "Error querying UCM table id for the article  : " . $aID . "<br>";
							$txt = "Error querying UCM table id for the article  : " . $aID . " and the menu id is " . $menuId . " so, SKIPPING THE ARTICLE!";
							file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
							continue;
						}
					} else {
						echo "Article id is 0 for the query : " . $varQuery . "<br>" . $conn->error;
						$txt = "Article id is 0 for the query : " . $varQuery . " and the menu id is " . $menuId . " so, SKIPPING THE ARTICLE!";
						file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
						continue;
					}
				}
			} else {
                // Nested query execution failed
                echo "Error in Nested Query : " . $varQuery . "<br>";
				continue;
            }
		} else {
			echo "Query is empty for the menu : " . $menuId . "<br>";
			// debug file
			$txt = "Query is empty for the menu : " . $menuId . " so, SKIPPING!";
			file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
			continue;
		}
		$txt = "SUCCESS LOG  : " . $menuId . " - " . $tagId . " - " . $cnt;
		file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
		$cnt = 0;
									
		// Update Menus.
		$link = 'index.php?option=com_tags&view=tag&layout=list&id[0]=' . $tagId . '&types[0]=1';
		$link = mysqli_real_escape_string($conn, $link);
		$cid = 29;
		$param = '{"show_tag_title":"","tag_list_show_tag_image":"","tag_list_show_tag_description":"1","tag_list_image":"","tag_list_description":"","tag_list_orderby":"","tag_list_orderby_direction":"ASC","tag_list_show_item_image":"","tag_list_show_item_description":"","tag_list_item_maximum_characters":"","filter_field":"","show_pagination_limit":"","display_num":"50","show_pagination":"","show_pagination_results":"","tag_list_show_date":"","date_format":"","return_any_or_all":"","include_children":"","show_feed_link":"","menu-anchor_title":"","menu-anchor_css":"","menu_image":"","menu_image_css":"","menu_text":1,"menu_show":1,"page_title":"","show_page_heading":"0","page_heading":"","pageclass_sfx":"","menu-meta_description":"","menu-meta_keywords":"","robots":"","secure":0}';
		
		$usql = "UPDATE j17_menu SET link = ?, component_id = ? , params = ? WHERE id = ?";
		$ustmt = $conn->prepare($usql);
		$ustmt->bind_param("sssi", $link, $cid, $param, $menuId);

		// Execute the update query
		if ($ustmt->execute()) {
			// Check if any rows were affected by the update
			$rowCount = $ustmt->affected_rows;
			if ($rowCount > 0) {
				echo "Menu Update successful. $rowCount row(s) updated.";
				// debug file
				$txt = "SUCCESS for the menu : " . $menuId;
				file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
			} else {
				echo "No rows updated for Menu. The ID may not exist in the table. SKIPPING!";
				// debug file
				$txt = "No rows updated for the menu : " . $menuId;
				file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
				// Close the statement and connection
				$ustmt->close();
				continue;
			}
		} else {
			echo "Error executing update query: " . $conn->error;
			// debug file
			$txt = "Error executing update statement for the menu : " . $menuId . " so, SKIPPING!";
			file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
			// Close the statement and connection
			$ustmt->close();			
			continue;
		}

		// Close the statement and connection
		$ustmt->close();
		
		$usql = "UPDATE fj_remove set flag = 1 where menuid = ?";
		$ustmt = $conn->prepare($usql);
		$ustmt->bind_param("i", $menuId);
		$ustmt->execute();
		$ustmt->close();
    }
	// debug file
	$txt = "Done whith the Data fetch while loop.";
	file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
} else {
    // Query execution failed
    echo "Error : " . $sql . "<br>" . $conn->error;
	$txt = "Error : " . $sql;
	file_put_contents($file, $txt.PHP_EOL, FILE_APPEND);
}
		
// Close the database connection
$conn->close();

echo " Script completed!";

?>
