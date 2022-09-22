<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//credentials
require('config.php');

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$defaultQuery = "";
$sql="";
if(!empty($_GET['sql'])) 
{
    $defaultQuery = $_GET['sql'];
    //echo $defaultQuery . " =? " . $sqlQuery . "<hr>";
}
//Send Query or Commment submission
if(!empty($_POST['queryName'])) 
{
    $queryName = $_POST['queryName'];
    $email = $_POST['email'];
    $sql = $_POST['sqlCommand'];
    $comment = $_POST['comment'];
    //print_r($_POST);
    $defaultQuery=$sql;

    $stmt = $conn->prepare("INSERT INTO queries (`page`, `name`, `sql`, `priority`, `comments`, `email`) VALUES  ('covid',?,?,'-1',?,?)");
    $stmt->bind_param("ssss", $queryName, $sql, $comment, $email); // 's' specifies the variable type => 'string'
    $stmt->execute();
}

date_default_timezone_set('America/Los_Angeles');
$lastPull = filemtime("covid.db");
?>

<!doctype html>
<html>

<head>
  <meta charset="utf8">
  <title>Covid SQL</title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
  <!--eCharts and SQL.JS-->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.46.0/codemirror.css">
  <link rel="stylesheet" href="demo.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.46.0/codemirror.js"></script>
  <script src="./js/echarts-en.min.js"></script>
</head>

<body>
  <h1>Covid SQL</h1>
  <main>
    <h4>Query list:</h4>
    <select id="queryList" onchange="setQuery(this.value)" class="custom-select">
        <?php
        $sql = "SELECT * FROM surrette_sql.queries where page='covid' and priority<>-1 order by priority asc, id asc;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $sqlQuery = $row['sql'];
                if($defaultQuery=="")
                {
                    $defaultQuery = $sqlQuery;
                }
                $selectedText="";
                if($defaultQuery == str_replace("\r", "", $sqlQuery)) //correct for newlines \n vs \n\r
                {
                    $selectedText = "selected";
                }
                echo '<option ' . $selectedText . ' value="' . $row['sql'] . '">' . $row["name"] . '</option>';
            }
        }
        ?>
    </select>
    <hr>
    <!--
    <select id="state" class="custom-select">
        <option selected>California</option>
    </select>
    <select id="county" class="custom-select">
        <option selected>Santa Clara</option>
    </select>
    -->
    <h4 for='commands'>SQL:</h4>
	<p><a target="_blank" href="http://surrette.net/sql/">Learn SQL</a></p>
    <textarea id="commands" name="commands"><?php echo $defaultQuery; ?></textarea>
    <p>Datasource: <a target="_blank" href="https://github.com/nytimes/covid-19-data">https://github.com/nytimes/covid-19-data</a><small> (last pulled <?php echo date ("m-d-Y H:i T", $lastPull);?>)</small><a target="_blank" href="https://www.nytimes.com/interactive/2020/us/coronavirus-us-cases.html"> - NYTimes interactive data visualizations</a><br>
    </p>
    <div id="error" class="error"></div>
    <button id="execute" class="button">Execute SQL<br><small>(CTRL-Enter)</small></button>
    <hr>
    <!--
    <button id='savedb' class="button">Save Database</button>
    <label class="button">Load Database (SQLite file): <input type='file' id='dbfile'></label>
    -->
    <!-- Modal -->
    <div class="modal fade" id="sendModal" tabindex="-1" role="dialog" aria-labelledby="sendModalLabel" aria-hidden="true">
        <div class="modal-dialog" id="myModal" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendModalLabel">Send Query or Comment</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="commentForm" method="post">
                        <input type="hidden" id="sqlCommand" name="sqlCommand" value="">
                        <div class="form-group">
                            <label for="queryName">Query Name</label>
                            <input type="text" class="form-control" name="queryName" id="queryName" placeholder="">
                            <small id="queryNameHelp" class="form-text text-muted">Interesting queries will be added to dropdown after review<br>(leave Query Name blank to send comment only)</small>
                        </div>
                        <div class="form-group">
                            <label for="formGroupExampleInput">Comment</label>
                            <textarea class="form-control" name="comment" id="comment" placeholder=""></textarea>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" name="email" id="email" aria-describedby="emailHelp" placeholder="Enter email">
                            <small id="emailHelp" class="form-text text-muted">(optional)</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  	<div id="main" style="width: 100%;height:400px"></div>
    <!-- Button trigger modal -->
    <button type="button" class="btn btn-primary" onclick="loadModal()" data-toggle="modal" data-target="#sendModal">Send Query or Comment</button>
    <button id="savecsv" class="button">Save Results as CSV</button>
    <button id="getURL" class="button" onclick="getURL()">Generate Shareable Link</button>
    <div id="link"></div>
    <hr>
    <pre id="output"></pre>
  </main>

  <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.46.0/mode/sql/sql.min.js"></script> -->
  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    <!--<script type="text/javascript" src="./js/bootstrap.min.js"></script>-->

    <script src='https://cdn.jsdelivr.net/npm/sql.js@0.4.0/js/sql.js'></script>
    <script type="text/javascript" src="gui.js"></script>
    <script type="text/javascript" src="graph.js"></script>
    <script type="text/javascript" src="csv.js"></script>
    <script type="text/javascript" src="page.js"></script>
</body>

</html>