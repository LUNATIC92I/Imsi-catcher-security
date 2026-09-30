<?php
declare(strict_types=1);

use App\Core\Database;
use App\Models\{User, Operator, Cell, Device, ConnectionEvent, Anomaly, Alert, LabScore, Bts};

return static function (): void {
    $pdo = lunatic_sqlite();
    Database::setConnection($pdo);

    // Roles + users (real Argon2id)
    $pdo->exec("INSERT INTO roles(name,label,permissions) VALUES('admin','Admin','[\"*\"]')");
    $uid = User::create('admin', 'a@l.lab', 'admin1234', 1);
    T::ok('User::create id', $uid > 0);
    $u = User::findByUsername('admin');
    T::eq('User role joined', 'admin', $u['role_name'] ?? null);
    T::ok('Argon2id verify', password_verify('admin1234', $u['password_hash']));
    T::ok('wrong pw rejected', !password_verify('nope', $u['password_hash']));

    // Infra chain
    $pdo->exec("INSERT INTO operators(code,name,mcc,mnc,country) VALUES('OPERATOR-LAB-01','LabTel A','901','01','Labland')");
    $pdo->exec("INSERT INTO bts(code,operator_id,lat,lng,coverage_m) VALUES('BTS-001',1,48.85,2.35,1500)");
    $pdo->exec("INSERT INTO cells(code,bts_id,operator_id,cell_id_num,lac,technology,frequency_mhz,power_dbm,is_legitimate,is_rogue) VALUES('CELL-001',1,1,1234,10,'4G',1800.2,-80,1,0)");
    $pdo->exec("INSERT INTO cells(code,bts_id,operator_id,cell_id_num,lac,technology,frequency_mhz,power_dbm,is_legitimate,is_rogue) VALUES('CELL-ROGUE-001',1,1,64000,1,'2G',900.2,-40,0,1)");
    $pdo->exec("INSERT INTO sims(code,operator_id,imsi_fake) VALUES('SIM-0001',1,'FAKE-90100000000001')");
    $pdo->exec("INSERT INTO devices(code,sim_id,imei_fake,model,current_cell_id,signal_dbm,connection_state,lat,lng) VALUES('DEVICE-0001',1,'FAKE-IMEI-000000001','VirtualPhone',1,-75,'connected',48.85,2.35)");

    T::eq('Operator::all count', 1, count(Operator::all(10)));
    T::eq('Bts join operator_code', 'OPERATOR-LAB-01', Bts::withOperator(10)[0]['operator_code'] ?? null);
    T::eq('Cell::rogues count', 1, count(Cell::rogues()));
    T::eq('Device join imsi_fake', 'FAKE-90100000000001', Device::withContext(10)[0]['imsi_fake'] ?? null);

    // Events / anomalies / alerts
    $eid = ConnectionEvent::log(['device_id' => 1, 'cell_id' => 2, 'sim_id' => 1, 'event_type' => 'attach',
        'technology' => '2G', 'signal_dbm' => -42, 'risk_level' => 'HIGH', 'detail' => 'test']);
    T::ok('ConnectionEvent::log', $eid > 0);
    T::eq('Event recent device', 'DEVICE-0001', ConnectionEvent::recent(10)[0]['device_code'] ?? null);

    $aid = Anomaly::log(['event_id' => $eid, 'device_id' => 1, 'cell_id' => 2,
        'anomaly_type' => 'unknown_cell', 'risk_level' => 'CRITICAL', 'score' => 95, 'reason' => 'rogue']);
    T::eq('Anomaly recent score', 95, (int) (Anomaly::recent(10)[0]['score'] ?? 0));

    $alid = Alert::log(['anomaly_id' => $aid, 'device_id' => 1, 'cell_id' => 2,
        'event_type' => 'ROGUE_CELL_DETECTED', 'severity' => 'CRITICAL', 'message' => 'x']);
    T::eq('Alert simulation flag', 1, (int) (Alert::find($alid)['simulation'] ?? 0));
    Alert::setStatus($alid, 'contained');
    T::eq('Alert status update', 'contained', Alert::find($alid)['status'] ?? null);

    // Scoring
    LabScore::award(1, null, 20, 'detect_rogue_cell', 'ok');
    LabScore::award(1, null, 20, 'identify_downgrade', 'ok');
    T::eq('LabScore total', 40, LabScore::totalFor(1));
    T::eq('Leaderboard top', 40, (int) (LabScore::leaderboard(5)[0]['total'] ?? 0));
};
