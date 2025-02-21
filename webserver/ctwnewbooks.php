<?php

/*

Primo VE Edition

To customize this code for your institution with minimal editing, look for comments with *** at the start of them. Of course, feel free to edit as much as you like!

If you see bugs or a better way to code any of this, your feedback would be extremely welcome.

To set this up, you will need to generate an API key through the Exlibris Developers Center with read permissions for Analytics.

*/


<?php

//***Change $file and $pfile to your output file names. Even if you're not planning to use the standalone file for anything, it can nice for quick troubleshooting if the file displayed in Primo looks wrong. */

$file = 'newbooks.html'; //file for standalone
$pfile = 'pvenewbooks.html'; //file for primo embed

//***Replace the relative link to "bookstyle.css" if you named your CSS file something different.
$contents = '<!doctype html><html lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><title>New Books for CTW</title><link rel="stylesheet" type="text/css" href="bookstyle.css"><link rel="stylesheet" type ="text/css" href="tiny-slider.css"></head><body><ul class="booklist">'; //begin html file with metadata and stylesheet links

$failcount = 0; //sometimes the analytics api call just fails so much

while ($rowcount == 0) {

//***Put the link to your Analytics report in place of PATH_TO_YOUR_REPORT and your API key in place of YOUR_API_KEY. Note that the path needs to be encoded, e.g., %2F for / 
    $xml = simplexml_load_file('https://api-na.hosted.exlibrisgroup.com/almaws/v1/analytics/reports?path=PATH_TO_YOUR_REPORT&col_names=false&limit=100&apikey=YOUR_API_KEY');
 
	if ($xml === FALSE) {
		continue;
	}
	

	else {
			
		/* register the "rowset" namespace */
		
		$xml->registerXPathNamespace('rowset', 'urn:schemas-microsoft-com:xml-analysis:rowset');
		
		/* use xpath to get rows of interest */
		
		$result1 = $xml->xpath('/report/QueryResult/ResultXml/rowset:rowset/rowset:Row');
		
		/* using rowcount of 0 to repeat call until data is obtained addresses the issue that calls to the analytics API sometimes just fail;
		   Since I know for certain that there should be data in the report */
		
		$rowcount = count($result1);
	}
}

echo ("Got books.<br />");

/* unique-ify the results , since the report is on items, but what we really want is titles, and there may be item duplicates */
array_unique($result1);
/* randomize the list, to keep it interesting */
shuffle($result1);


/* parse the analytics report data */

//***Your Analytics report might not have columns that match mine! Make sure you adjust the column variables as needed
foreach ($result1 as $row) {
//	$call = (string) $row->Column7;
	$author = explode(',', $row->Column1);
	$mms = (string) $row->Column4;
	$title = (string) rtrim($row->Column6, " /");
	$isbns = explode(';', $row->Column2);
	
	echo $title . ": ";

	//*** Sub in your API key again, along with your vid, tab, and scope
		$linkson = file_get_contents('https://api-na.hosted.exlibrisgroup.com/primo/v1/search?vid=YOUR_VID&tab=YOUR_TAB&scope=YOUR_SCOPE&q=any,exact,' . $mms . '&apikey=YOUR_API_KEY');
		$cleanson = json_decode($linkson, false);
		$reallink = (string) $cleanson->docs[0]->pnx->control->recordid[0];
		
		foreach ($isbns as $value) { 
			$isbn = trim($value); //clean it up
			echo $isbn . ": ";
			
			$image = 'https://syndetics.com/index.php?client=primo&isbn=' . $isbn . '/mc.jpg'; //medium size thumbnail plz
			$fileSize = strlen(file_get_contents($image)); //so far this is somehow the least stupid way to weed out 1x1 nonsense covers
			
				if ($fileSize > 200) { //no scrubs
				//***Replace the Primo URL and vid below with yours
					$contents .= '<li class="newbooks"> <a target="_blank" rel="noopener noreferrer" href="https://YOUR_PRIMO_INSTANCE.exlibrisgroup.com/discovery/fulldisplay?docid=' . $reallink . '&context=L&vid=YOUR_VID">';
					$contents .= '<img class ="centered" src="' . 'https://syndetics.com/index.php?client=primo&isbn=' . $isbn . '/mc.jpg' . '"  alt="' . $title . '"></a></li>';
					echo $image . "<br />"; 
					continue 2;
			}

			//*** Google Books is a fallback in case Syndetics doesn't have a cover image available. You'll need your own API key for this (replace YOUR_GOOGLE_API_KEY).
			else {
				$googson = file_get_contents('https://www.googleapis.com/books/v1/volumes?q=' . $isbn . '&fields=items(volumeInfo(imageLinks%2Ctitle))&key=YOUR_GOOGLE_API_KEY'); //fyi google books has some issues, primarily that it might find a book by isbn and then not have a cover for it, so it goes somewhere weird. Also some bad isbn metadata
				$googparse = json_decode($googson, false);
				$image1 = (string)$googparse->items[0]->volumeInfo->imageLinks->thumbnail;
			//	echo "<br>Google did this :( " . $image1;
				$image = 'https://' . substr($image1, 7);
			//	echo '<br>Fixed Google URL: ' . $image . ' is it fixed? <br>';
					$googtitle = (string)$googparse->items[0]->volumeInfo->title; //trying to compare the beginning of the titles to avoid chaos
					$stitle = substr($title,0,5);
					$sgtitle = substr($googtitle,0,5);
			
					if ($image != "" && $stitle == $sgtitle) { //no blanks, no nonsense
					    //***Replace the Primo URL and view id below with yours
						$contents .= '<li class="newbooks"> <a target="_blank" rel="noopener noreferrer" href="https://YOUR_PRIMO_INSTANCE.primo.exlibrisgroup.com/discovery/fulldisplay?docid=' . $reallink . '&context=L&vid=YOUR_VID">';
						$contents .= '<img class ="centered" src="' . $image . '"  alt="' . $title . '"></a></li>';
						echo $image . "<br />";
						continue 2;
				}
			}
		}
		
}


//***Make sure shuffle.js is in the same folder as this file
$contents.= '</ul></body></html>'; //slap the shuffle on

//***Make sure tinyslide.js is in the same folder as this file.
$primoContents = str_replace('</body>', '<script src="newtinyslide.js"></script></body>', $contents); //slap the slider on primo

file_put_contents ($file, $contents);
file_put_contents ($pfile, $primoContents);

//*** Replace YOUR_SERVER with the URL where you're keeping pvenewbooks.html
echo '<br /> check out <a href="https://YOUR_SERVER/pvenewbooks.html">these new books</a>';

?>
