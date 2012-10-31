<?php

namespace Node;


interface IStream {
  public function getFD();
  public function getMode();
  public function close();
}
