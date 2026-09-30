<?php
declare(strict_types=1);

/**
 * LUNATIC MOBILE SECURITY LAB — SQLite demo seeder (NO Docker/MySQL needed).
 *
 * Builds a self-contained SQLite database and a synthetic dataset so the app
 * can be launched with zero infrastructure:
 *
 *   php database/seed_sqlite.php
 *   DB_CONNECTION=sqlite php -S 127.0.0.1:8080 -t public
 *
 * All identifiers are fictional (FAKE-*, MCC 9xx) and flagged is_simulated=1.
 * This is a demo/dev convenience; production uses MySQL via database/seed.php.
 */

require dirname(__DIR__) . '/app/Core/App.php';

use App\Core\App;

App::boot();
$path = getenv('DB_SQLITE_PATH') ?: (App::config('paths')['storage'] . '/lunatic.sqlite');
@unlink($path);

$pdo = new PDO('sqlite:' . $path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function say(string $m): void { echo $m . PHP_EOL; }
say('== LUNATIC SQLite demo seeder ==');
say('· db: ' . $path);

$pdo->exec(<<<SQL
CREATE TABLE roles(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT,label TEXT,permissions TEXT);
CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT,username TEXT UNIQUE,email TEXT,password_hash TEXT,role_id INT,is_active INT DEFAULT 1,last_login_at TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE operators(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,name TEXT,mcc TEXT,mnc TEXT,country TEXT,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE bts(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,operator_id INT,lat REAL,lng REAL,coverage_m INT,is_legitimate INT DEFAULT 1,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE cells(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,bts_id INT,operator_id INT,cell_id_num INT,lac INT,technology TEXT,frequency_mhz REAL,power_dbm INT,is_legitimate INT DEFAULT 1,is_rogue INT DEFAULT 0,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE sims(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,operator_id INT,imsi_fake TEXT,ki_placeholder TEXT DEFAULT 'SIMULATED-NO-KEY',is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE devices(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,sim_id INT,imei_fake TEXT,model TEXT,current_cell_id INT,signal_dbm INT,connection_state TEXT DEFAULT 'idle',lat REAL,lng REAL,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE device_mobility(id INTEGER PRIMARY KEY AUTOINCREMENT,device_id INT,lat REAL,lng REAL,cell_id INT,recorded_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE connection_events(id INTEGER PRIMARY KEY AUTOINCREMENT,device_id INT,cell_id INT,sim_id INT,event_type TEXT,technology TEXT,signal_dbm INT,risk_level TEXT DEFAULT 'LOW',detail TEXT,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE handovers(id INTEGER PRIMARY KEY AUTOINCREMENT,device_id INT,from_cell_id INT,to_cell_id INT,reason TEXT,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE anomalies(id INTEGER PRIMARY KEY AUTOINCREMENT,event_id INT,device_id INT,cell_id INT,anomaly_type TEXT,risk_level TEXT,score INT DEFAULT 0,reason TEXT,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE alerts(id INTEGER PRIMARY KEY AUTOINCREMENT,anomaly_id INT,device_id INT,cell_id INT,event_type TEXT,severity TEXT,status TEXT DEFAULT 'open',message TEXT,soc_exported INT DEFAULT 0,simulation INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE scenarios(id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT,name TEXT,category TEXT,description TEXT,difficulty TEXT,max_score INT DEFAULT 100,is_simulated INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE scenario_steps(id INTEGER PRIMARY KEY AUTOINCREMENT,scenario_id INT,step_order INT,title TEXT,description TEXT,step_type TEXT,payload TEXT);
CREATE TABLE lab_scores(id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INT,scenario_id INT,points INT DEFAULT 0,max_points INT DEFAULT 100,category TEXT,detail TEXT,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE quiz_results(id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INT,module TEXT,score INT DEFAULT 0,total INT DEFAULT 0,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE audit_logs(id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INT,username TEXT,action TEXT,scenario TEXT,ip_address TEXT,result TEXT,metadata TEXT,simulation INT DEFAULT 1,created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE rate_limits(id INTEGER PRIMARY KEY AUTOINCREMENT,bucket TEXT UNIQUE,hits INT DEFAULT 1,window_start INT);
SQL);
say('· schema created');

// Roles & users
$pdo->prepare('INSERT INTO roles(name,label,permissions) VALUES(?,?,?)')->execute(['admin','Administrateur','["*"]']);
$pdo->prepare('INSERT INTO roles(name,label,permissions) VALUES(?,?,?)')->execute(['analyst','Analyste SOC','["simulation.run","soc.push","soc.respond","audit.view"]']);
$pdo->prepare('INSERT INTO roles(name,label,permissions) VALUES(?,?,?)')->execute(['student','Étudiant','["simulation.run"]']);
$u = $pdo->prepare('INSERT INTO users(username,email,password_hash,role_id) VALUES(?,?,?,?)');
$u->execute(['admin','admin@lunatic.lab',password_hash('admin1234',PASSWORD_ARGON2ID),1]);
$u->execute(['analyst','analyst@lunatic.lab',password_hash('analyst1234',PASSWORD_ARGON2ID),2]);
$u->execute(['student','student@lunatic.lab',password_hash('student1234',PASSWORD_ARGON2ID),3]);
say('· roles + demo users (admin/admin1234, analyst/analyst1234, student/student1234)');

$pdo->beginTransaction();

// Operators
$opIds = [];
$op = $pdo->prepare('INSERT INTO operators(code,name,mcc,mnc,country) VALUES(?,?,?,?,?)');
for ($i = 1; $i <= 8; $i++) {
    $op->execute([sprintf('OPERATOR-LAB-%02d',$i),'LabTel '.chr(64+$i),(string)(900+$i),str_pad((string)$i,2,'0',STR_PAD_LEFT),'Labland']);
    $opIds[] = (int)$pdo->lastInsertId();
}

// BTS
$clat = 48.8566; $clng = 2.3522; $btsIds = [];
$b = $pdo->prepare('INSERT INTO bts(code,operator_id,lat,lng,coverage_m,is_legitimate) VALUES(?,?,?,?,?,1)');
for ($i = 1; $i <= 40; $i++) {
    $b->execute([sprintf('BTS-%03d',$i),$opIds[array_rand($opIds)],round($clat+mt_rand(-600,600)/10000,6),round($clng+mt_rand(-600,600)/10000,6),mt_rand(800,2200)]);
    $btsIds[] = (int)$pdo->lastInsertId();
}

// Cells
$techs = ['4G','4G','4G','3G','3G','2G','5G']; $cellIds = [];
$c = $pdo->prepare('INSERT INTO cells(code,bts_id,operator_id,cell_id_num,lac,technology,frequency_mhz,power_dbm,is_legitimate,is_rogue) VALUES(?,?,?,?,?,?,?,?,?,?)');
for ($i = 1; $i <= 120; $i++) {
    $tech = $techs[array_rand($techs)];
    $rogue = mt_rand(1,100) <= 5 ? 1 : 0;
    $c->execute([
        $rogue?sprintf('CELL-ROGUE-%03d',$i):sprintf('CELL-%03d',$i),
        $btsIds[array_rand($btsIds)],$opIds[array_rand($opIds)],
        $rogue?mt_rand(60000,65000):mt_rand(1000,59000),mt_rand(1,300),
        $rogue?'2G':$tech,(float)mt_rand(800,3800),$rogue?mt_rand(-48,-32):mt_rand(-95,-60),
        $rogue?0:1,$rogue,
    ]);
    $cellIds[] = (int)$pdo->lastInsertId();
}

// SIMs & devices
$simIds = [];
$s = $pdo->prepare('INSERT INTO sims(code,operator_id,imsi_fake) VALUES(?,?,?)');
for ($i = 1; $i <= 200; $i++) {
    $s->execute([sprintf('SIM-%04d',$i),$opIds[array_rand($opIds)],'FAKE-'.(900+mt_rand(1,8)).str_pad((string)$i,10,'0',STR_PAD_LEFT)]);
    $simIds[] = (int)$pdo->lastInsertId();
}
$models = ['VirtualPhone','LabDroid','SimPhone-X','TestHandset','EduMobile']; $states=['idle','connected','searching']; $devIds=[];
$d = $pdo->prepare('INSERT INTO devices(code,sim_id,imei_fake,model,current_cell_id,signal_dbm,connection_state,lat,lng) VALUES(?,?,?,?,?,?,?,?,?)');
for ($i = 1; $i <= 200; $i++) {
    $d->execute([sprintf('DEVICE-%04d',$i),$simIds[$i-1]??null,'FAKE-IMEI-'.str_pad((string)mt_rand(0,999999999),9,'0',STR_PAD_LEFT),$models[array_rand($models)],$cellIds[array_rand($cellIds)],mt_rand(-105,-55),$states[array_rand($states)],round($clat+mt_rand(-700,700)/10000,6),round($clng+mt_rand(-700,700)/10000,6)]);
    $devIds[] = (int)$pdo->lastInsertId();
}

// Events (recent 24h)
$etypes=['attach','detach','handover','location_update','paging','identity_request','downgrade','reject'];
$risks=['LOW','LOW','LOW','MEDIUM','MEDIUM','HIGH','CRITICAL']; $t4=['2G','3G','4G','5G'];
$ev = $pdo->prepare('INSERT INTO connection_events(device_id,cell_id,sim_id,event_type,technology,signal_dbm,risk_level,detail,created_at) VALUES(?,?,?,?,?,?,?,?,?)');
for ($i = 1; $i <= 2000; $i++) {
    $type=$etypes[array_rand($etypes)];
    $risk=($type==='downgrade'||$type==='identity_request')?'HIGH':$risks[array_rand($risks)];
    $ev->execute([$devIds[array_rand($devIds)],$cellIds[array_rand($cellIds)],null,$type,$t4[array_rand($t4)],mt_rand(-110,-50),$risk,'Événement réseau simulé',date('Y-m-d H:i:s',time()-mt_rand(0,86400))]);
}

// Handovers
$h = $pdo->prepare('INSERT INTO handovers(device_id,from_cell_id,to_cell_id,reason) VALUES(?,?,?,?)');
for ($i = 1; $i <= 300; $i++) { $h->execute([$devIds[array_rand($devIds)],$cellIds[array_rand($cellIds)],$cellIds[array_rand($cellIds)],'Mobilité simulée']); }

// Anomalies
$atypes=['unknown_cell','downgrade','high_power','plmn_mismatch','cell_spoofing','identity_exposure','sudden_cell_change'];
$an = $pdo->prepare('INSERT INTO anomalies(device_id,cell_id,anomaly_type,risk_level,score,reason) VALUES(?,?,?,?,?,?)');
for ($i = 1; $i <= 200; $i++) {
    $sc=mt_rand(20,100); $lv=$sc>=80?'CRITICAL':($sc>=55?'HIGH':($sc>=30?'MEDIUM':'LOW'));
    $an->execute([$devIds[array_rand($devIds)],$cellIds[array_rand($cellIds)],$atypes[array_rand($atypes)],$lv,$sc,'Anomalie simulée']);
}

// Alerts from top anomalies
$al = $pdo->prepare('INSERT INTO alerts(anomaly_id,device_id,cell_id,event_type,severity,status,message,simulation) VALUES(?,?,?,?,?,?,?,1)');
foreach ($pdo->query("SELECT * FROM anomalies WHERE risk_level IN ('HIGH','CRITICAL') ORDER BY score DESC LIMIT 30") as $a) {
    $al->execute([$a['id'],$a['device_id'],$a['cell_id'],'ROGUE_CELL_DETECTED',$a['risk_level'],'open','Menace simulée: '.$a['anomaly_type']]);
}

// Scenarios (5)
$sc = $pdo->prepare('INSERT INTO scenarios(code,name,category,description,difficulty,max_score) VALUES(?,?,?,?,?,100)');
foreach ([['rogue_cell','Rogue Cell'],['downgrade','Downgrade 4G→2G'],['identity_exposure','Identity Exposure'],['location_tracking','Location Tracking'],['cell_spoofing','Cell Spoofing']] as $x) {
    $sc->execute([sprintf('SCENARIO-%s',strtoupper($x[0])),$x[1],$x[0],'Scénario pédagogique simulé',(['beginner','intermediate','advanced'])[array_rand(['a','b','c'])]]);
}

$pdo->commit();
say('· data: 8 operators, 40 BTS, 120 cells, 200 SIMs/devices, 2000 events, 200 anomalies, 30 alerts, 5 scenarios');
say('');
say('== DONE. Launch:  DB_CONNECTION=sqlite php -S 127.0.0.1:8080 -t public ==');
