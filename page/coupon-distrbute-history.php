<?php

require_once("../db_connect.php");
include("../function/login_status_inspect.php");
include("../function/rebuildURL.php");



// $sql = "SELECT coupon_id, recieved_time, GROUP_CONCAT(user_id ORDER BY user_id ASC) AS user_ids
//         FROM users_coupon
//         WHERE coupon_id =? AND user_id=? 
//         GROUP BY coupon_id, recieved_time;"

$sql = "SELECT uc.coupon_id, c.name AS coupon_name, uc.recieved_time, GROUP_CONCAT(uc.user_id ORDER BY uc.user_id ASC) AS user_ids
        FROM users_coupon uc
        INNER JOIN coupon c ON uc.coupon_id = c.coupon_id
        WHERE 1 = 1";
$params = []; // 用來裝條件
$types = ""; // 用來紀錄條件型別


// 搜尋 優惠券名稱
if (isset($_GET["search_coupon"]) && !empty($_GET["search_coupon"])) {
    $search_coupon = "%" . $_GET["search_coupon"] . "%";
    $sql .= " AND c.name LIKE ?";
    array_push($params, $search_coupon);
    $types .= "s";
}

// 將 同coupon_id 且 同發送時間 視為一次發送事件
$sql .= " GROUP BY uc.coupon_id, uc.recieved_time"; //GROUP BY 要寫在 WHERE 之後

// 排序條件 
if(!isset($_GET["sort"])){
    $sort = "time_desc";
    $sql .= " ORDER BY uc.recieved_time DESC";
}else{
    $sort = $_GET["sort"];
    switch($sort){
        case "time_desc":
            $sql .= " ORDER BY uc.recieved_time DESC";
            break;
        case "time_asc":
            $sql .= " ORDER BY uc.recieved_time ASC";
            break;
        case "couponId_asc":
            $sql .= " ORDER BY c.coupon_id ASC";
            break;
        case "couponId_desc":
            $sql .= " ORDER BY c.coupon_id DESC";
            break;
    }
}

// 撈資料
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>優惠券發送歷史</title>
    <?php include("../css/css_Joe.php"); ?>
    <style>
        .coupon-input-bar{
            width: 50px;
        }
        p{
            margin-top: auto;
            margin-bottom: auto;
        }
    </style>
</head>

<body>
    <?php include("../modules/dashboard-header_Joe.php"); ?>

    <div class="container-fluid d-flex flex-row px-4">

        <?php include("../modules/dashboard-sidebar_Joe.php"); ?>

        <div class="main col neumorphic p-5">
            <h2 class="mb-4 d-flex justify-content-center">優惠券發送歷史</h2>
            <!-- 篩選器 -->
            <div class=""></div>
            <div class="py-2 mt-5 coupon-filter d-flex justify-content-start">
                <form action="">
                    <div class="input-group">
                        <input type="search" class="form-control" name="search_coupon" value="<?php echo isset($_GET["search_coupon"]) ? $_GET["search_coupon"] : "" ?>" placeholder="搜尋優惠券名稱">
                        <select class="form-select" aria-label="Default select example" name="sort">
                            <option <?php echo $sort == "time_desc" ? "selected" : ""; ?> value="time_desc">依發券時間（新⭢舊）</option>
                            <option <?php echo $sort == "time_asc" ? "selected" : ""; ?> value="time_asc">依發券時間（舊⭢新）</option>
                            <option <?php echo $sort == "couponId_asc" ? "selected" : ""; ?> value="couponId_asc">依優惠券id（少⭢多）</option>
                            <option <?php echo $sort == "couponId_desc" ? "selected" : ""; ?> value="couponId_desc">依優惠券id（多⭢少）</option>
                        </select>
                        <button class="btn btn-custom" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </div>
                </form>
            </div>

            <!-- 資料表格 -->
            <div class="d-flex justify-content-center">
                <table class="table mt-3">
                    <thead class="table-pink">
                        <tr>
                            <th class="text-center align-middle">優惠券id</th>
                            <th class="text-center align-middle">優惠券名稱</th>
                            <th class="text-center align-middle">發送人數</th>
                            <th class="text-center align-middle">發送時間<br></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row) : ?>
                            <tr>
                                <td class="text-center align-middle"><?php echo $row["coupon_id"];?></td>
                                <td class="text-center align-middle"><?php echo $row["coupon_name"];?></td>
                                <td class="text-center align-middle">
                                    <div class="d-flex justify-content-end">
                                        <p class="me-3">
                                            <?php 
                                            $userAmount = count(explode(',', $row['user_ids']));
                                            echo $userAmount;
                                            ?>人
                                        </p>
                                        <form action="./coupon-distrbute-history-detail.php">
                                            <input type="hidden" name="recieved_time" value="<?= $row['recieved_time'] ?>">
                                            <input type="hidden" name="coupon_id" value="<?= $row['coupon_id'] ?>">
                                            <button class="btn btn-custom" type="submmit" title="查看發送名單"><i class="fa-solid fa-user-group"></i></button>
                                        </form>
                                    </div>
                                </td>
                                <td class="text-center align-middle"><?php echo $row["recieved_time"];?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    
    </div>

    <!-- 顯示新增和編輯的成功or失敗訊息 -->
    <!-- 有時間可以用中介頁面來避免使用GET -->
    <!-- 有時間可以用別的設計取代alert -->
    <?php
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            echo "<script type='text/javascript'>alert('$message');</script>";
        }
    ?>


    <!-- Javascript 寫這裡 -->
    <?php include("../js.php"); ?>
    <script>

    </script>
</body>

</html>