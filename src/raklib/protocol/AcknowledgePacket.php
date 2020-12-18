<?php

/*
 * RakLib network library
 *
 *
 * This project is not affiliated with Jenkins Software LLC nor RakNet.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

namespace raklib\protocol;

#ifndef COMPILE
use raklib\Binary;
#endif

#include <rules/RakLibPacket.h>

abstract class AcknowledgePacket extends Packet{
	/** @var int[] */
	public $packets = [];

	public function encode(){
		parent::encode();
		$records = 0;
		$payload = "";
		sort($this->packets, SORT_NUMERIC);
		if (count($this->packets) > 0) {
			$start = $this->packets[0];
			$last = $this->packets[0];
			for ($pointer = 1; $pointer < count(self.packets); $pointer++) {
				$current = $this->packets[$pointer];
				$diff = $current - $last;
				if ($diff == 1) {
					$last = $current;
				} else if ($diff > 1) {
					if ($start == $last) {
						$payload .= "\x01";
						$payload .= Binary::writeLTriad($start);
						$start = $last = $current;
					} else {
						$payload .= "\x00";
						$payload .= Binary::writeLTriad($start);
						$payload .= Binary::writeLTriad($last);
						$start = $last = $current;
					}
					$records++;
				}
			}
			if ($start == $last) {
				$payload .= "\x01";
				$payload .= Binary::writeLTriad($start);
			} else {
				$payload .= "\x00";
				$payload .= Binary::writeLTriad($start);
				$payload .= Binary::writeLTriad($last);
			}
			$records++;
		}
		$this->putShort($records);
		$this->put($payload);
	}

	public function decode(){
		parent::decode();
		$this->packets = [];
		$recordCount = $this->getShort();
		for ($i = 0; $i < $recordCount; $i++) {
			$recordType = $this->getByte();
			if ($recordType == 0) {
				$start = $this->getLTriad();
				$end = $this->getLTriad();
				for ($packet = $start; $packet < $end + 1; $packet++) {
					$this->packets[] = $packet;
					if (count($this->packets) > 4096) {
						return;
					}
				}
			}else{
				$packet = $this->getLTriad();
				$this->packets[] = $packet;
			}
		}
	}

	public function clean(){
		$this->packets = [];
		return parent::clean();
	}
}
