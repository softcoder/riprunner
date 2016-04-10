<?php
// ==============================================================
//	Copyright (C) 2016 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

// Hack since the native interface requries php 5.4 or higher
interface JsonSerializable {
    public function jsonSerialize();
}