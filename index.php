
<?php
header("Content-type: text/plain; charset=utf-8");
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

require_once __DIR__ . "/crest.php";

try {
    $deal_id = $_GET["dealId"];
    if (!$deal_id) {
        echo "No Deal Id";
        exit();
    }

    $result = CRest::call("user.get", [
        // test departments(triada) : 435, 7, 3
        "UF_DEPARTMENT" => [1099 ],//production uf_department=1099
        "ACTIVE" => "true",

    ]);
    print_r($result);

    if (count($result["result"]) == 0) {
        echo "No active users";
        exit();
    }

    $active_users = [];
    foreach ($result["result"] as $item => $value) {
        $first_worktime = CRest::call("timeman.status", [
            "USER_ID" => $value["ID"],
        ]);

        if ($first_worktime["result"]["STATUS"] == "OPENED") {
            $active_users[] = $value["ID"];
        }
    }

    $all_users = file_get_contents("last_user_id");
    $all_users = (array) json_decode($all_users);

    $next_user = null;
    foreach ($active_users as $k => $user) {
        if (!in_array($user, $all_users)) {
            $next_user = $user;
            break;
        }
    }
    var_dump($next_user);
    if ($next_user) {
        $all_users[] = $next_user;
    } else {
        // всем работникам были назначены сделки, нужно очистить массив
        $next_user = $active_users[0];
        unset($all_users);
        $all_users = [$next_user];
    }

    $result = CRest::call("crm.deal.update", [
        "id" => $deal_id,
        "fields" => [
            "ASSIGNED_BY_ID" => $next_user,
        ],
    ]);

    $json_user_list = json_encode($all_users);
    file_put_contents("last_user_id", $json_user_list);
} catch (Exception $e) {
    echo "Caught exception: ", $e->getMessage(), "\n";
}
