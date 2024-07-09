<?php

declare(strict_types=1);

class PersonalWorkOffTracker {
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO('mysql:host=localhost;dbname=work_off_tracker', 'beko', '9999');
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function addRecord($arrived_at, $leaved_at) {
        try {
            $arrived_at_dt = new DateTime($arrived_at);
            $leaved_at_dt = new DateTime($leaved_at);

            $interval = $arrived_at_dt->diff($leaved_at_dt);
            $hours = $interval->h + ($interval->days * 24);
            $minutes = $interval->i;

            $required_work_off = sprintf('%02d:%02d:00', $hours, $minutes);

            $sql = "INSERT INTO dailys (arrived_at, leaved_at, required_work_off) VALUES (:arrived_at, :leaved_at, :required_work_off)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':arrived_at', $arrived_at);
            $stmt->bindParam(':leaved_at', $leaved_at);
            $stmt->bindParam(':required_work_off', $required_work_off);

            $stmt->execute();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function fetchRecords($page_id) {
        $offset = ($page_id - 1) * 5;
        $sql = "SELECT * FROM dailys ORDER BY id DESC LIMIT :offset, 5";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt;

        $total_hours = 0;
        $total_minutes = 0;

        if ($result->rowCount() > 0) {
            echo '<form action="" method="post">';
            echo '<table class="table table-striped">';
            echo '<thead class="table-dark"><tr><th>#</th><th>Arrived at</th><th>Leaved at</th><th>Required work off</th><th>Worked off</th></tr></thead>';
            echo '<tbody>';
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $worked_off_class = $row["worked_off"] ? 'class="worked-off"' : '';
                echo "<tr $worked_off_class>";
                echo '<td>' . $row["id"] . '</td>';
                echo '<td>' . $row["arrived_at"] . '</td>';
                echo '<td>' . $row["leaved_at"] . '</td>';
                echo '<td>' . $row["required_work_off"] . '</td>';
                echo '<td><button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#confirmModal" data-id="' . $row["id"] . '">Done</button></td>';
                echo '</tr>';

                if (!$row["worked_off"]) {
                    list($hours, $minutes, $seconds) = explode(':', $row["required_work_off"]);
                    $total_hours += (int)$hours;
                    $total_minutes += (int)$minutes;
                }
            }
            $total_hours += floor($total_minutes / 60);
            $total_minutes = $total_minutes % 60;

            echo '<tr><td colspan="4" class="text-end fw-bold">Total work off hours</td><td>' . $total_hours . ' hours and ' . $total_minutes . ' min.</td></tr>';
            echo '</tbody>';
            echo '</table>';
            echo '<button type="submit" name="export" class="btn btn-primary">Export as CSV</button>';
            echo '</form>';
        }
    }

    public function updateWorkedOff($id) {
        try {
            $sql = "UPDATE dailys SET worked_off = 1 WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function exportCSV() {
        try {
            $sql = "SELECT * FROM dailys";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt;

            $filename = "work_off_report_" . date('Ymd') . ".csv";
            $file = fopen('php://output', 'w');

            $header = array("ID", "Arrived At", "Leaved At", "Required Work Off", "Worked Off");
            fputcsv($file, $header);

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($file, $row);
            }

            fclose($file);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            exit();
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function getTotalPages($records_per_page) {
        try {
            $sql = "SELECT COUNT(*) as total FROM dailys";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ceil($row['total'] / $records_per_page);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}