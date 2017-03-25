<?php

    $arguments = getopt('t:o:s:dih', array('tags:', 'order:', 'skip:', 'no-download', 'no-information', 'help'));

    if(isset($arguments['tags'])) {
        $search = $arguments['tags'];
    } else if (isset($arguments['t'])) {
        $search = $arguments['t'];
    } else {
        help();
        exit();
    }

    $search = array_filter(preg_split("/(,|\s)/", $search));

    if(isset($arguments['order'])) {
        $order = $arguments['order'];
    } else if (isset($arguments['o'])) {
        $order = $arguments['o'];
    } else {
        $order = null;
    }

    $order = trim($order);

    if(isset($arguments['skip'])) {
        $skip = $arguments['skip'];
    } else if (isset($arguments['s'])) {
        $skip = $arguments['s'];
    } else {
        $skip = array();
    }

    $skip = array_filter(is_array($skip) ? $skip : preg_split("/(,|\s)/", $skip));

    $file = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'skip.txt';
    if(file_exists($file)) {
        $skip = array_replace($skip, file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    }

    if(isset($arguments['no-information'])) {
        $no_generate = true;
    } else if (isset($arguments['i'])) {
        $no_generate = true;
    } else {
        $no_generate = false;
    }

    if(isset($arguments['no-download'])) {
        $no_download = true;
    } else if (isset($arguments['d'])) {
        $no_download = true;
    } else {
        $no_download = false;
    }

    if(empty($arguments) || isset($arguments['help']) || isset($arguments['h'])) {
        help();
        exit;
    }

    function help() {
        echo "E621 Download Utility\n";
        echo "Usage: php e621.php --tags <tag1,tag2> [options]\n";
        echo "Options:\n";
        echo "  --order <order>\n";
        echo "  --skip <tag1,tag2>\n";
        echo "  --no-download\n";
        echo "  --no-information\n";
        echo "  --help\n";        
    }

    $folder = getcwd() . DIRECTORY_SEPARATOR . implode('_', $search) . '_' . date('d_m_Y');
    if($no_download == false && $no_generate == true) {
        $files = $folder;
    } else {
        $files = $folder . DIRECTORY_SEPARATOR . 'files';
    }

    if(file_exists($folder)) {
        die('Error: Destination folder already exists.');
    }

    if(count($search) > 6) {
        delete_directory($folder);
        echo "Error: You can only retrieve up to 6 tags.";
        exit;
    }

    $results = array();

    $page = 1;

    function get($url) { 
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)');
        curl_setopt($curl, CURLOPT_URL, $url);
        if(!$result = curl_exec($curl)) {
            $result = false;
        }
        curl_close($curl);
        return $result;
    }

    $results['meta']['search'] = $search;
    $results['meta']['order'] = $order;
    $results['meta']['skip'] = $skip;
    $results['meta']['total'] = 0;

    echo "Searching...\n";

    $continue = true;
    while($continue == true) {
        $request = json_decode(get('https://e621.net/post/index.json?limit=320&page=' . $page . '&tags=' . implode('+', $search) . '+order:' . ($order ? $order : '-id')));
        if(!is_array($request)) {
            echo "Error: Invalid response.";
            exit;
        }
        if(!count($request)) {
            $continue = false;
        } else {
            foreach($request as $result) {
                $md5 = $result->md5;
                $created = $result->created_at->s;
                $file = $result->file_url;
                $tags = explode(' ', $result->tags);
                if(count(array_intersect($skip, $tags))) {
                    continue;
                }
                $results['posts'][$result->id] = array(
                    'md5' => $md5,
                    'created' => $created,
                    'file' => $file,
                    'tags' => $tags,
                );
                $results['meta']['total']++;
            }
            $page++;
        }
    }
    
    if(isset($results['posts']) && count($results['posts'])) {
        echo "Found " . $results['meta']['total'] . " posts...\n";
    } else {
        echo "No results found.\n";
        exit;
    }

    mkdir($folder);
    if($no_download == false && $no_generate == false) {
        mkdir($files);
    }

    if($no_download == false) {
        $download = 0;
        foreach($results['posts'] as $post) {
            $file = $post['file'];
            $filename = $files . DIRECTORY_SEPARATOR . pathinfo($post['file'])['basename'];
            file_put_contents($filename, get($file));
            touch($filename, $post['created']);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if(in_array(finfo_file($finfo, $filename), array('application/xml', 'text/plain', 'text/html'))) {
                die('Invalid files.');
            }
            finfo_close($finfo);
            $download++;
            echo "Downloading post " . $download . " of " . $results['meta']['total'] . "...\n";
        }
        echo "All posts downloaded.\n";
    }

    if($no_generate == false) {
        if($no_download == true) {
            file_put_contents(getcwd() . DIRECTORY_SEPARATOR . implode('_', $search) . '_' . date('d_m_Y') . '.json', json_encode($results));
        } else {
            file_put_contents($folder . DIRECTORY_SEPARATOR . 'information.json', json_encode($results));
        }
        echo "Post information saved.\n";
    }

?>