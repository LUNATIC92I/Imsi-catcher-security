<?php
declare(strict_types=1);

/**
 * Minimal test harness for LUNATIC MOBILE SECURITY LAB.
 * No external dependencies (no PHPUnit) — pure PHP assertions.
 */

require dirname(__DIR__) . '/app/Core/App.php';

\App\Core\App::boot();

final class T
{
    public static int $pass = 0;
    public static int $fail = 0;
    private static array $failures = [];

    public static function ok(string $label, bool $cond): void
    {
        if ($cond) {
            self::$pass++;
            fwrite(STDOUT, "  \033[32mPASS\033[0m  {$label}\n");
        } else {
            self::$fail++;
            self::$failures[] = $label;
            fwrite(STDOUT, "  \033[31mFAIL\033[0m  {$label}\n");
        }
    }

    public static function eq(string $label, mixed $expected, mixed $actual): void
    {
        $cond = $expected === $actual;
        if (!$cond) {
            $label .= sprintf(' (expected %s, got %s)', var_export($expected, true), var_export($actual, true));
        }
        self::ok($label, $cond);
    }

    public static function summary(): int
    {
        $total = self::$pass + self::$fail;
        fwrite(STDOUT, sprintf("\n== %d/%d passed, %d failed ==\n", self::$pass, $total, self::$fail));
        return self::$fail === 0 ? 0 : 1;
    }
}

/** Build an in-memory SQLite mirror of the schema (portable subset). */
function lunatic_sqlite(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec(<<<SQL
CREATE TABLE roles(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT,label TEXT,permissions TEXT);
CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT,username TEXT,email TEXT,password_hash TEXT,role_id INT,is_active INT DEFAULT 1,last_login_at TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE operators(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,name TEXT,mcc TEXT,mnc TEXT,country TEXT,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE bts(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,operator_id INT,lat REAL,lng REAL,coverage_m INT,is_legitimate INT DEFAULT 1,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE cells(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,bts_id INT,operator_id INT,cell_id_num INT,lac INT,technology TEXT,frequency_mhz REAL,power_dbm INT,is_legitimate INT DEFAULT 1,is_rogue INT DEFAULT 0,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE sims(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,operator_id INT,imsi_fake TEXT,ki_placeholder TEXT DEFAULT 'SIMULATED-NO-KEY',is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE devices(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,sim_id INT,imei_fake TEXT,model TEXT,current_cell_id INT,signal_dbm INT,connection_state TEXT DEFAULT 'idle',lat REAL,lng REAL,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE connection_events(id INTEGER PRIMARY KEY AUTOINCREMENT,device_id INT,cell_id INT,sim_id INT,event_type TEXT,technology TEXT,signal_dbm INT,risk_level TEXT DEFAULT 'LOW',detail TEXT,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE anomalies(id INTEGER PRIMARY KEY AUTOINCREMENT,event_id INT,device_id INT,cell_id INT,anomaly_type TEXT,risk_level TEXT,score INT DEFAULT 0,reason TEXT,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE alerts(id INTEGER PRIMARY KEY AUTOINCREMENT,anomaly_id INT,device_id INT,cell_id INT,event_type TEXT,severity TEXT,status TEXT DEFAULT 'open',message TEXT,soc_exported INT DEFAULT 0,simulation INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE handovers(id INTEGER PRIMARY KEY AUTOINCREMENT,device_id INT,from_cell_id INT,to_cell_id INT,reason TEXT,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE lab_scores(id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INT,scenario_id INT,points INT DEFAULT 0,max_points INT DEFAULT 100,category TEXT,detail TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
SQL);
    return $pdo;
}
