<?php
class PetManager {
    public static function updatePet($pdo, $user_id) {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime("-1 day"));

        try {
            // 1. Fetch current pet status
            $stmt = $pdo->prepare("SELECT * FROM user_pets WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $pet = $stmt->fetch();

            if (!$pet) {
                // Create initial pet if none exists
                $insertStmt = $pdo->prepare("INSERT INTO user_pets (user_id, pet_type, streak_count, level, xp, last_move_date, evolution_stage) VALUES (?, 'cat', 1, 1, 0, ?, 'egg')");
                $insertStmt->execute([$user_id, $today]);
                return;
            }

            // Increment streak and XP on every transaction so the user can see it grow immediately!
            $new_streak = $pet['streak_count'] + 1;
            $xp_gain = 10 + (int)($new_streak / 5); // Bonus XP for higher streaks

            // 3. Handle Leveling & Evolution
            $new_xp = $pet['xp'] + $xp_gain;
            $new_level = floor($new_xp / 100) + 1;

            $stage = 'egg';
            if ($new_level >= 20) {
                $stage = 'legend';
            } elseif ($new_level >= 10) {
                $stage = 'adult';
            } elseif ($new_level >= 5) {
                $stage = 'teen';
            } elseif ($new_level >= 2) {
                $stage = 'baby';
            }

            // 4. Update Database
            $update = $pdo->prepare("
                UPDATE user_pets
                SET streak_count = ?,
                    xp = ?,
                    level = ?,
                    evolution_stage = ?,
                    last_move_date = ?
                WHERE user_id = ?
            ");
            $update->execute([$new_streak, $new_xp, $new_level, $stage, $today, $user_id]);
        } catch (Exception $e) {
            error_log("PetManager Error: " . $e->getMessage());
            // Don't throw - allow transaction to succeed even if pet update fails
        }
    }
}
