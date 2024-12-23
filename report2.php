<?php
$serverName = "LAPTOP-LSFR3CIB\\SQLEXPRESS01";  // Change to your SQL Server name
$connectionOptions = [
    "Database" => "attendance",  // Change to your database name
    "Uid" => "",  // Use your SQL Server username
    "PWD" => ""   // Use your SQL Server password
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}
date_default_timezone_set('Asia/Manila'); 


$current_date = date('Y-m-d');


if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];


    $current_time = new DateTime();
    $formatted_time = $current_time->format('Y-m-d H:i:s');

 
    $update_sql = "
        UPDATE attendance
        SET status = 'Time Out', paused_time = ?, timeout_time = ?
        WHERE student_id = ? AND status = 'Present' AND CAST(date AS DATE) = ?";  

    $params = array($formatted_time, $formatted_time, $student_id, $current_date);
    $stmt = sqlsrv_query($conn, $update_sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

   
    header("Location: report2.php");
    exit();
}


$sql = "
    SELECT i.student_id, i.name, a.status, 
           a.time, a.paused_time, a.timeout_time, a.date
    FROM interns i
    LEFT JOIN attendance a 
    ON i.student_id = a.student_id
    WHERE CAST(a.date AS DATE) = ?  -- Only fetch records for today
    ORDER BY i.name";  

$stmt = sqlsrv_query($conn, $sql, array($current_date));

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Roboto:400,100,300,500">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="image/favicon.png" type="image/png">
    <link rel="stylesheet" href="styles/report.css">
    <title>Rendered Time for Interns</title>
</head>

<body>

    <nav>
        <ul style="list-style-type: none; padding: 0;">
            <li style="display: flex; align-items: center; margin-top: 10px;">
                <a href="report2.php" class="logo-link" style="display: flex; align-items: center; text-decoration: none;"> <!-- Change report.php or report2.php -->
                    <img src="image/icon-72.png" class="logo-img" style="max-height: 51px; margin-right: 10px;" />
                    <span class="logo-text" style="font-family: 'Robotolightnew', sans-serif; line-height: 1.2; text-align: left; font-size: 20px;">
                        Internship<br>Management
                    </span>
                </a>
            </li>
            <li style="margin-top: 25px; margin-left: 20px; font-family: 'Robotolightnew', sans-serif; font-size: 18px;">
                <a href="form.php" style="text-decoration: none;">Internship Registration Form</a>
            </li>
            <li style="margin-top: 25px; margin-left: 20px; font-family: 'Robotolightnew', sans-serif; font-size: 18px;">
                <a href="attendance.php" style="text-decoration: none;">Internship Attendance</a>
            </li>
        </ul>
    </nav>

    <h1 style="margin-top:40px;">Internship Attendance Reports and Rendered Time</h1>

    <h2>Attendance Report for: <?php echo $current_date; ?></h2>

    <table border="1">
        <tr>
            <th>Student ID</th>
            <th>Intern Name</th>
            <th>Status</th>
            <th>Time In</th>
            <th>Total Rendered Time</th>
            <th>Time Out</th>
            <th>Attendance Date</th> 
            <th>Mark Time Out</th>
        </tr>
        <?php
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $time_display = ($row['time'] !== null) ? $row['time']->format('h:i A') : 'N/A';

            
            if ($row['paused_time'] !== null) {
                $paused_time = $row['paused_time'];
                $paused_time_display = $paused_time->format('h:i');
            } else {
                $paused_time_display = 'N/A';
            }

            
            $timeout_time_display = ($row['timeout_time'] !== null) ? $row['timeout_time']->format('h:i A') : 'N/A';

            
            $attendance_date_display = ($row['date'] !== null) ? (new DateTime($row['date']->format('Y-m-d H:i:s')))->format('F j, Y') : 'N/A';

            
            if ($row['status'] == 'Present') {
                $current_time = new DateTime();
                $attendance_time = $row['time'];

                
                $time_diff = $current_time->diff($attendance_time);
                $rendered_time = $time_diff->format('%h hours, %i minutes');
            } elseif ($row['status'] == 'Time Out' && $row['paused_time'] !== null) {
                $attendance_time = $row['time'];
                $paused_time = $row['paused_time'];

                
                $time_diff = $paused_time->diff($attendance_time);
                $rendered_time = $time_diff->format('%h hours, %i minutes');
            } else {
                $rendered_time = 'N/A';
            }

            echo "<tr>
            <td>" . $row['student_id'] . "</td>
            <td>" . $row['name'] . "</td>
            <td>" . $row['status'] . "</td>
            <td>" . $time_display . "</td>
            <td>" . $rendered_time . "</td>
            <td>" . $timeout_time_display . "</td>
            <td>" . $attendance_date_display . "</td>
            <td>
                     <a href='?student_id=" . $row['student_id'] . "' class='btn'>
                    <i class='fa-regular fa-clock'></i> Time out
                    </a>
            </td>
            </tr>";
        }
        ?>
    </table>
</body>
</html>