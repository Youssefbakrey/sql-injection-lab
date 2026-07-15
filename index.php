<?php

$host = getenv("DB_HOST") ?: "mysql";
$user = "labuser";
$pass = "labpass";
$dbname = "labdb";
 
$conn = new mysqli($host, $user, $pass, $dbname);
 
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}


$conn->query("CREATE TABLE IF NOT EXISTS flag (
    id INT PRIMARY KEY AUTO_INCREMENT,
    flag_value VARCHAR(100) NOT NULL
)");

$check_flag = $conn->query("SELECT COUNT(*) as cnt FROM flag");
$row = $check_flag->fetch_assoc();
if ($row['cnt'] == 0) {
    $conn->query("INSERT INTO flag (flag_value) VALUES ('flag{Header_SQLi_Injection_Kali}')");
}


$user_input = isset($_POST['user_input']) ? $_POST['user_input'] : '';
$input_message = '';

if (!empty($user_input)) {
    // استخدام Prepared Statements للحماية
    $stmt = $conn->prepare("INSERT INTO logs (user_agent, request_time, is_vulnerable) VALUES (?, NOW(), 0)");
    $stmt->bind_param("s", $user_input);
    
    if ($stmt->execute()) {
        $input_message = "<span style='color:lightgreen;'>✅ Input inserted securely (Prepared Statement)</span>";
    } else {
        $input_message = "<span style='color:red;'>❌ Error: " . htmlspecialchars($stmt->error) . "</span>";
    }
    $stmt->close();
}


$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';


$vuln_query = "INSERT INTO logs (user_agent, request_time, is_vulnerable) VALUES ('$user_agent', NOW(), 1)";

$vuln_status = '';
$vuln_error = '';

try {
    $conn->query($vuln_query);
    $vuln_status = "<span style='color:#ffa657;'>⚠️ Vulnerable INSERT executed from User-Agent</span>";
} catch (mysqli_sql_exception $e) {
    $vuln_status = "<span style='color:orange;'>⚠️ Injection detected in User-Agent: " . htmlspecialchars($e->getMessage()) . "</span>";
    $vuln_error = $e->getMessage();
}


$select_query = "SELECT id, user_agent, request_time FROM logs WHERE user_agent = '$user_agent'";
$select_result = $conn->query($select_query);

$rows_html = "";
$found_flag = "";

if ($select_result) {
    while ($row = $select_result->fetch_assoc()) {
        if (strpos($row['user_agent'], 'flag{') !== false) {
            $found_flag = $row['user_agent'];
        }
        $rows_html .= "<tr>
            <td>{$row['id']}</td>
            <td>" . htmlspecialchars($row['user_agent']) . "</td>
            <td>{$row['request_time']}</td>
        </tr>";
    }
} else {
    $rows_html = "<tr><td colspan='3' style='color:red;'>SELECT Error: " . $conn->error . "</td></tr>";
}


$all_logs_result = $conn->query("SELECT id, user_agent, request_time, is_vulnerable FROM logs ORDER BY id DESC LIMIT 30");
$all_rows_html = "";
if ($all_logs_result) {
    while ($row = $all_logs_result->fetch_assoc()) {
        $vuln_tag = isset($row['is_vulnerable']) && $row['is_vulnerable'] ? "🔴 User-Agent" : "🟢 Input";
        $all_rows_html .= "<tr>
            <td>{$row['id']}</td>
            <td>" . htmlspecialchars($row['user_agent']) . "</td>
            <td>{$row['request_time']}</td>
            <td>{$vuln_tag}</td>
        </tr>";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Header SQLi Lab - Kali</title>
    <style>
        body { background:#0d1117; color:#e6edf3; font-family: Consolas, monospace; padding:30px; }
        h1 { color:#58a6ff; border-bottom: 2px solid #30363d; padding-bottom: 10px; }
        h2 { color:#f78166; margin-top:40px; }
        h3 { color:#ffa657; }
        table { border-collapse: collapse; width:100%; margin-top:10px; }
        th, td { border:1px solid #30363d; padding:8px 12px; text-align:left; }
        th { background:#161b22; }
        .box { background:#161b22; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid #30363d; }
        .vulnerable-box { border: 2px solid #f78166; background: #1c1515; }
        .secure-box { border: 2px solid #238636; background: #151c15; }
        code { color:#79c0ff; }
        .flag-box { background: #1c2333; border: 3px solid #f0883e; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .flag-text { color: #ffa657; font-size: 2em; font-weight: bold; letter-spacing: 2px; }
        input[type="text"] { background: #0d1117; color: #e6edf3; border: 1px solid #30363d; padding: 10px; width: 70%; border-radius: 4px; font-family: Consolas, monospace; }
        input[type="submit"] { background: #238636; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        input[type="submit"]:hover { background: #2ea043; }
        .payload-examples { background: #1c2333; padding: 10px; border-radius: 4px; margin-top: 10px; border-left: 3px solid #f0883e; }
        .note { background: #1c1c1c; padding: 10px; border-radius: 4px; border-left: 3px solid #58a6ff; margin-top: 10px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.8em; font-weight: bold; }
        .badge-danger { background: #da3633; color: white; }
        .badge-success { background: #238636; color: white; }
        .badge-warning { background: #d29922; color: white; }
        .server-info { background: #0d1117; padding: 10px; border-radius: 4px; border: 1px solid #30363d; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>SQL Injection Lab </h1>
    
    <div class="box secure-box">
        <h3 style="color: #238636;">Input Field </h3>
        <form method="POST" action="">
            <input type="text" name="user_input" placeholder="...." value="<?php echo htmlspecialchars($user_input); ?>">
            <input type="submit" value="Send">
        </form>
        <?php if (!empty($user_input)): ?>
            <div style="margin-top:10px;">
                <strong>Status:</strong> <?php echo $input_message; ?><br>
                <strong>Your input:</strong> <code><?php echo htmlspecialchars($user_input); ?></code>
            </div>
            
        <?php endif; ?>
    </div>

    <?php if ($found_flag): ?>
    <div class="flag-box">
        <strong>🏴 تم العثور على الفلاج!</strong><br>
        <span class="flag-text"><?php echo htmlspecialchars($found_flag); ?></span>
    </div>
    <?php endif; ?>

    </body>
</html>
<?php

$host = "localhost";
$user = "root";
$pass = "";  // في Kali بكلمة سر sudo، غالباً فاضي
$dbname = "labdb";
 
$conn = new mysqli($host, $user, $pass, $dbname);
 
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}


$conn->query("CREATE TABLE IF NOT EXISTS flag (
    id INT PRIMARY KEY AUTO_INCREMENT,
    flag_value VARCHAR(100) NOT NULL
)");

$check_flag = $conn->query("SELECT COUNT(*) as cnt FROM flag");
$row = $check_flag->fetch_assoc();
if ($row['cnt'] == 0) {
    $conn->query("INSERT INTO flag (flag_value) VALUES ('flag{Header_SQLi_Injection_Kali}')");
}


$user_input = isset($_POST['user_input']) ? $_POST['user_input'] : '';
$input_message = '';

if (!empty($user_input)) {
    // استخدام Prepared Statements للحماية
    $stmt = $conn->prepare("INSERT INTO logs (user_agent, request_time, is_vulnerable) VALUES (?, NOW(), 0)");
    $stmt->bind_param("s", $user_input);
    
    if ($stmt->execute()) {
        $input_message = "<span style='color:lightgreen;'>✅ Input inserted securely (Prepared Statement)</span>";
    } else {
        $input_message = "<span style='color:red;'>❌ Error: " . htmlspecialchars($stmt->error) . "</span>";
    }
    $stmt->close();
}


$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';


$vuln_query = "INSERT INTO logs (user_agent, request_time, is_vulnerable) VALUES ('$user_agent', NOW(), 1)";

$vuln_status = '';
$vuln_error = '';

try {
    $conn->query($vuln_query);
    $vuln_status = "<span style='color:#ffa657;'>⚠️ Vulnerable INSERT executed from User-Agent</span>";
} catch (mysqli_sql_exception $e) {
    $vuln_status = "<span style='color:orange;'>⚠️ Injection detected in User-Agent: " . htmlspecialchars($e->getMessage()) . "</span>";
    $vuln_error = $e->getMessage();
}


$select_query = "SELECT id, user_agent, request_time FROM logs WHERE user_agent = '$user_agent'";
$select_result = $conn->query($select_query);

$rows_html = "";
$found_flag = "";

if ($select_result) {
    while ($row = $select_result->fetch_assoc()) {
        if (strpos($row['user_agent'], 'flag{') !== false) {
            $found_flag = $row['user_agent'];
        }
        $rows_html .= "<tr>
            <td>{$row['id']}</td>
            <td>" . htmlspecialchars($row['user_agent']) . "</td>
            <td>{$row['request_time']}</td>
        </tr>";
    }
} else {
    $rows_html = "<tr><td colspan='3' style='color:red;'>SELECT Error: " . $conn->error . "</td></tr>";
}


$all_logs_result = $conn->query("SELECT id, user_agent, request_time, is_vulnerable FROM logs ORDER BY id DESC LIMIT 30");
$all_rows_html = "";
if ($all_logs_result) {
    while ($row = $all_logs_result->fetch_assoc()) {
        $vuln_tag = isset($row['is_vulnerable']) && $row['is_vulnerable'] ? "🔴 User-Agent" : "🟢 Input";
        $all_rows_html .= "<tr>
            <td>{$row['id']}</td>
            <td>" . htmlspecialchars($row['user_agent']) . "</td>
            <td>{$row['request_time']}</td>
            <td>{$vuln_tag}</td>
        </tr>";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Header SQLi Lab - Kali</title>
    <style>
        body { background:#0d1117; color:#e6edf3; font-family: Consolas, monospace; padding:30px; }
        h1 { color:#58a6ff; border-bottom: 2px solid #30363d; padding-bottom: 10px; }
        h2 { color:#f78166; margin-top:40px; }
        h3 { color:#ffa657; }
        table { border-collapse: collapse; width:100%; margin-top:10px; }
        th, td { border:1px solid #30363d; padding:8px 12px; text-align:left; }
        th { background:#161b22; }
        .box { background:#161b22; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid #30363d; }
        .vulnerable-box { border: 2px solid #f78166; background: #1c1515; }
        .secure-box { border: 2px solid #238636; background: #151c15; }
        code { color:#79c0ff; }
        .flag-box { background: #1c2333; border: 3px solid #f0883e; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .flag-text { color: #ffa657; font-size: 2em; font-weight: bold; letter-spacing: 2px; }
        input[type="text"] { background: #0d1117; color: #e6edf3; border: 1px solid #30363d; padding: 10px; width: 70%; border-radius: 4px; font-family: Consolas, monospace; }
        input[type="submit"] { background: #238636; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        input[type="submit"]:hover { background: #2ea043; }
        .payload-examples { background: #1c2333; padding: 10px; border-radius: 4px; margin-top: 10px; border-left: 3px solid #f0883e; }
        .note { background: #1c1c1c; padding: 10px; border-radius: 4px; border-left: 3px solid #58a6ff; margin-top: 10px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.8em; font-weight: bold; }
        .badge-danger { background: #da3633; color: white; }
        .badge-success { background: #238636; color: white; }
        .badge-warning { background: #d29922; color: white; }
        .server-info { background: #0d1117; padding: 10px; border-radius: 4px; border: 1px solid #30363d; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>SQL Injection Lab </h1>
    
    <div class="box secure-box">
        <h3 style="color: #238636;">Input Field </h3>
        <form method="POST" action="">
            <input type="text" name="user_input" placeholder="...." value="<?php echo htmlspecialchars($user_input); ?>">
            <input type="submit" value="Send">
        </form>
        <?php if (!empty($user_input)): ?>
            <div style="margin-top:10px;">
                <strong>Status:</strong> <?php echo $input_message; ?><br>
                <strong>Your input:</strong> <code><?php echo htmlspecialchars($user_input); ?></code>
            </div>
            
        <?php endif; ?>
    </div>

    <?php if ($found_flag): ?>
    <div class="flag-box">
        <strong>🏴 تم العثور على الفلاج!</strong><br>
        <span class="flag-text"><?php echo htmlspecialchars($found_flag); ?></span>
    </div>
    <?php endif; ?>

    </body>
</html>
