<?php
/*
 * ToDO! Get websites - Begin
 */
function get_website_data ( $url ) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    try {
        $data = curl_exec($curl);
    } catch (\Throwable $th) {
        throw $th;
    }
    libxml_use_internal_errors(true);

    curl_close($curl);

    $doc = new DOMDocument();
    $doc->loadHTML($data);

    $xpath = new DOMXPath($doc);
    $datatable = $xpath->query('//*[@class="datatable"]');

    if ( $datatable != NULL ) {

        $tbody = $datatable[0]->getElementsByTagName("tbody")[0];
        $td = $tbody->getElementsByTagName("td");

        //error handle
        $website = '';
        if ( $td[7] != NULL ) {
            $website = trim($td[7]-> textContent.PHP_EOL);
        }

        $data_array[$url] = [
            0 => trim($td[0]-> textContent.PHP_EOL),
            1 => trim($td[1]-> textContent.PHP_EOL),
            2 => trim($td[2]-> textContent.PHP_EOL),
            3 => trim($td[3]-> textContent.PHP_EOL),
            4 => trim($td[4]-> textContent.PHP_EOL),
            5 => trim($td[5]-> textContent.PHP_EOL),
            6 => trim($td[6]-> textContent.PHP_EOL),
            7 => $website,
        ];

        return $data_array;

    }else {
        return "Cannot reach the site";
    }
}
function go_page_data( $url ) {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);

    try {
        $data = curl_exec($curl);
    } catch (\Throwable $th) {
        throw $th;
    }
    libxml_use_internal_errors(true);

    curl_close($curl);

    $doc = new DOMDocument();
    $doc->loadHTML($data);

    $xpath = new DOMXPath($doc);

    $datatable = $xpath->query('//*[@class="datatable"]');

    $tbody = $datatable[0]->getElementsByTagName("tbody")[0];

    $tr = $tbody->getElementsByTagName("tr");

    $website = [];

    foreach ( $tr as $single_tr ) {
        $a = $single_tr->getElementsByTagName("td")[0]-> getElementsByTagName("a")[0];
        $link = $a -> getAttribute("href");
        $website[] = get_website_data ( "https://www.iata.org". $link );
    }

    return $website;
}

/*
 * ToDO! Main curl function
 */
function do_curl_website($website_url){
    $ch = curl_init(); // create curl resource
    curl_setopt($ch, CURLOPT_URL, $website_url); // set url
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return the transfer as a string
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE); // use https
    curl_setopt($ch, CURLOPT_CAINFO, "cacert.pem"); // pass CA certs
    // or delete two above line and use curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); //if website response time is greater than 60seconds cut the connection and return to $body false
    // Some websites don't like crawlers, so pretend to be a browser
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36'
    ]);
    //curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch,CURLOPT_ENCODING , "gzip");

    $html = curl_exec($ch); // $output contains the output string
    if (empty($html)) { return 0; }
    $info = curl_getinfo($ch); //get curl header info
    curl_close($ch); // close curl resource to free up system resources

    $dom = new domDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    return array($dom,$info);
}

/*
 * ToDO! Add results returned to db
 */
function add_results_to_db($seo_checks,$id){
    global $conn;
    $set = '';
    foreach($seo_checks as $key=>$value){
        if(empty($set)){
            $set .= $key.'='.$value;
        }else{
            $set .= ', '.$key.'='.$value;
        }
    }
    $set .= ', status=1'; // status 1 when checks completed
    $sql = "UPDATE SEOTechniques SET $set WHERE id='$id'";
    if ($conn->query($sql) === TRUE) { return 1; }
    else { return $conn->error; }
}