<?php

namespace Node;

interface ICURLRequest {
  public function getHandle();
  public function status($status);
  public function data($chunk);
  public function end();
}
