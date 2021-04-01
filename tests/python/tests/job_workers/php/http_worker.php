<?php

function do_http_worker() {
  switch($_SERVER["PHP_SELF"]) {
    case "/test_simple_cpu_job": {
      test_simple_cpu_job();
      return;
    }
    case "/test_cpu_job_and_rpc_usage_between": {
      test_cpu_job_and_rpc_usage_between();
      return;
    }
    case "/test_cpu_job_and_mc_usage_between": {
      test_cpu_job_and_mc_usage_between();
      return;
    }
    case "/test_job_script_timeout_error": {
      test_job_script_timeout_error();
      return;
    }
    case "/test_job_errors": {
      test_job_errors();
      return;
    }
    case "/test_jobs_in_wait_queue": {
      test_jobs_in_wait_queue();
      return;
    }
    case "/test_several_waits_for_one_job": {
      test_several_waits_for_one_job();
      return;
    }
    case "/test_complex_scenario": {
      require_once "ComplexScenario/_http_scenario.php";
      run_http_complex_scenario();
      return;
    }
  }

  critical_error("unknown test");
}

function send_jobs($context, float $timeout = -1.0): array {
  $ids = [];
  foreach ($context["data"] as $arr) {
    $req = new X2Request;
    $req->tag = (string)$context["tag"];
    $req->master_port = (int)$context["master-port"];
    $req->arr_request = (array)$arr;
    $req->sleep_time_sec = (int)$context["job-sleep-time-sec"];
    $req->error_type = (string)$context["error-type"];
    $ids[] = kphp_job_worker_start($req, $timeout);
  }
  return $ids;
}

function gather_jobs(array $ids): array {
  $result = [];
  foreach($ids as $id) {
    $resp = kphp_job_worker_wait($id);
    if ($resp instanceof X2Response) {
      $result[] = ["data" => $resp->arr_reply, "stats" => $resp->stats];
    } else if ($resp instanceof KphpJobWorkerResponseError) {
      $result[] = ["error" => $resp->getError(), "error_code" => $resp->getErrorCode()];
    }
  }
  return $result;
}

function test_simple_cpu_job() {
  $context = json_decode(file_get_contents('php://input'));
  $ids = send_jobs($context);
  echo json_encode(["jobs-result" => gather_jobs($ids)]);
}

function test_cpu_job_and_rpc_usage_between() {
  $context = json_decode(file_get_contents('php://input'));
  $ids = send_jobs($context);

  $master_port_connection = new_rpc_connection("localhost", $context["master-port"]);
  $stat_id = rpc_tl_query_one($master_port_connection, ['_' => 'engine.stat']);
  $self_stats = rpc_tl_query_result_one($stat_id);

  echo json_encode(["stats" => $self_stats, "jobs-result" => gather_jobs($ids)]);
}

function test_cpu_job_and_mc_usage_between() {
  $context = json_decode(file_get_contents('php://input'));
  $ids = send_jobs($context);

  $mc = new McMemcache();
  $mc->addServer("localhost", (int)$context["master-port"]);
  $self_stats = $mc->get("stats");

  echo json_encode(["stats" => $self_stats, "jobs-result" => gather_jobs($ids)]);
}

function test_job_script_timeout_error() {
  $context = json_decode(file_get_contents('php://input'));
  $ids = send_jobs($context, (float)$context["script-timeout"]);
  echo json_encode(["jobs-result" => gather_jobs($ids)]);
}

function test_job_errors() {
  $context = json_decode(file_get_contents('php://input'));
  $ids = send_jobs($context);
  echo json_encode(["jobs-result" => gather_jobs($ids)]);
}

function raise_error(string $err) {
  echo json_encode(["error" => $err]);
}

function test_jobs_in_wait_queue() {
  $context = json_decode(file_get_contents('php://input'));
  $ids = send_jobs($context);

  $wait_queue = rpc_queue_create($ids);

  $id = rpc_queue_next($wait_queue, 0.2);
  if ($id !== false) {
    raise_error("Too short wait time in wait_queue");
    return;
  }

  $job_sleep_time = (float)$context["job-sleep-time-sec"];
  $result = [];
  while (!rpc_queue_empty($wait_queue)) {
    $ready_id = rpc_queue_next($wait_queue, $job_sleep_time + 0.2);
    if ($ready_id === false) {
      raise_error("Too long wait time in wait_queue");
      return;
    }
    $result[$ready_id] = gather_jobs([$ready_id])[0];
  }
  ksort($result);

  echo json_encode(["jobs-result" => array_values($result)]);
}

function test_several_waits_for_one_job() {
  $context = json_decode(file_get_contents('php://input'));
  $ids = send_jobs($context);

  $id = $ids[0];
  $job_sleep_time = (float)$context["job-sleep-time-sec"];

  for ($i = 0; $i < 5; $i++) {
    $res = kphp_job_worker_wait($id, $job_sleep_time / 100.0);
    if ($res !== null) {
      raise_error("Too short wait time $i");
      return;
    }
  }

  echo json_encode(["jobs-result" => gather_jobs($ids)]);
}