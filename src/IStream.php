<?php

namespace Node;


interface IStream {
  public function getHandle();
  public function getMode();
  public function close();
}
