<?php
$memcache = new Memcache();

$memcache->addServer('127.0.0.1'); // edit here if your memcached server differs from localhost

if (isset($_GET['del'])) {
    $memcache->delete($_GET['del']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_GET['flush'])) {
    $memcache->flush();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_GET['set'])) {
    $memcache->set($_GET['set'], $_GET['value']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$list = array();

function getData(){
    global $memcache;

    $allSlabs = $memcache->getExtendedStats('slabs');
    $result = current($allSlabs);
    if(!$result){
        return false;
    }
    return $allSlabs;
}
// $allSlabs = $memcache->getExtendedStats('slabs');
while (($allSlabs = getData()) == false);

foreach($allSlabs as $server => $slabs) {
    foreach($slabs AS $slabId => $slabMeta) {
        $cdump = $memcache->getExtendedStats('cachedump',(int)$slabId);
        foreach($cdump AS $server => $entries) {
            if($entries) {
                foreach($entries AS $eName => $eData) {
                    $list[$eName] = array(
                        'key' => $eName,
                        'value' => $memcache->get($eName)
                    );
                }
            }
        }
    }
}
ksort($list);
$listJson = json_encode($list);
$listJson = str_replace('\"','\\\\"',$listJson);
// print_r($listJson);exit;

?>
<head>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/jsoneditor@9.9.0/dist/jsoneditor.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsoneditor@9.9.0/dist/jsoneditor.min.css">

    <script src="https://cdn.jsdelivr.net/npm/tablesorter@2.31.3/dist/js/jquery.tablesorter.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tablesorter@2.31.3/dist/css/theme.default.min.css">
</head>
<body>

<div class="container" style="width: 1280px;">
    <h3>memcached</h3>
    <table class="tablesorter table table-bordered table-hover table-striped">
        <thead>
        <tr>
            <th width="10%" style="overflow: hidden">key</th>
            <th>value</th>
            <th width="5%" class="sorter-false"> </th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($list as $i): ?>
            <tr>
                <td style="text-align: center;vertical-align: middle;"><?php echo $i['key'] ?></td>
                <td id="json-editor-<?php echo $i['key'] ?>"></td>
                <td style="text-align: center;vertical-align: middle;"><a href="memcached.php?del=<?php echo $i['key'] ?>"> del </a>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <center>
        <a href="memcached.php?flush=1">FLUSH</a> <br />
        <br />
        <a href="#" onclick="memcachedSet()">SET</a>
    </center>

    <script type="text/javascript">
        String.prototype.replaceAll = function(search, replacement) {
            const target = this;
            return target.replace(new RegExp(search, 'g'), replacement);
        };
        const jsonData = '<?php echo $listJson ?>';
        const data = JSON.parse(jsonData);

        for (const i in data) {
            console.log("json-editor-" + data[i].key);

            let container = document.getElementById("json-editor-" + data[i].key);
            let options = {
                mode: 'view', // code | text | tree | view
                mainMenuBar: false,
                isExpand: false
            }
            let editor = new JSONEditor(container, options);

            let initialJson = data[i].value;
            editor.set(initialJson)
            editor.collapseAll();
        }

        $(document).ready(function(){
            $("table").tablesorter({ sortList: [[0,0],[1,0]] });
        });

        function memcachedSet() {
            let key = prompt("Key: ");
            let value = prompt("Value: ");

            window.location.href = "memcached.php?set="+ key +"&value=" + value;
        }
    </script>
</body>