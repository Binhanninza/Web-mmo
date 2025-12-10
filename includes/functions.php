<?php
// Hàm thay đổi số dư an toàn (Transaction)
function change_balance($user_id, $amount, $type, $ref_id = null, $desc = '') {
    global $conn;
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT money FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows == 0) throw new Exception("User không tồn tại");
        $u = $res->fetch_assoc();

        $new_bal = $u['money'] + $amount;
        if ($new_bal < 0) throw new Exception("Số dư không đủ");

        $up = $conn->prepare("UPDATE users SET money = ? WHERE id = ?");
        $up->bind_param("ii", $new_bal, $user_id);
        $up->execute();

        $in = $conn->prepare("INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, description, reference_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $in->bind_param("isissss", $user_id, $type, $amount, $u['money'], $new_bal, $desc, $ref_id);
        $in->execute();

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
?>
