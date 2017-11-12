<?php

/*
 * EconomyS, the massive economy plugin with many features for PocketMine-MP
 * Copyright (C) 2013-2016  onebone <jyc00410@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace onebone\economyapi\provider;

use onebone\economyapi\EconomyAPI;

interface Provider{

	public function __construct(EconomyAPI $plugin);

	public function open();

	/**
	 * 계정이 존재하는지 여부를 반환합니다.
	 *
	 * @param string $player
	 *
	 * @return bool
	 */
	public function accountExists(string $player) : bool;

	/**
	 * 계정을 생성합니다.
	 *
	 * @param string $player
	 * @param int|float $defaultMoney
	 *
	 * @return bool
	 */
	public function createAccount(string $player, $defaultMoney = 1000) : bool;

	/**
	 * 계정을 삭제합니다.
	 *
	 * @param string $player
	 *
	 * @return bool
	 */
	public function removeAccount(string $player) : bool;

	/**
	 * 플레이어의 돈을 반환합니다.
	 *
	 * @param string $player
	 *
	 * @return float|bool
	 */
	public function getMoney(string $player);

	/**
	 * 플레이어의 돈을 설정합니다.
	 *
	 * @param string $player
	 * @param int|float $amount
	 *
	 * @return bool
	 */
	public function setMoney(string $player, $amount) : bool;

	/**
	 * 플레이어의 돈을 수량만큼 추가합니다.
	 *
	 * @param string $player
	 * @param int|float $amount
	 *
	 * @return bool
	 */
	public function addMoney(string $player, $amount) : bool;

	/**
	 * 플레이어의 돈을 수량만큼 줄입니다.
	 *
	 * @param string $player
	 * @param int|float $amount
	 *
	 * @return bool
	 */
	public function reduceMoney(string $player, $amount) : bool;

	/**
	 * 플레이어의 돈 순위를 반환합니다.
	 * 만약 플레이어가 존재하지 않을 시, false를 반환합니다.
	 *
	 * @param string $player
	 *
	 * @return int|false
	 */
	public function getRank(string $player);

	/**
	 * 해당 순위에 있는 플레이어를 반환합니다.
	 * 만약 플레이어가 존재하지 않을 시, false를 반환합니다.
	 *
	 * @param int $rank
	 *
	 * @return string|false
	 */
	public function getPlayerByRank(int $rank);

	/**
	 * 모든 플레이어의 돈 데이터를 반환합니다.
	 *
	 * @return array
	 */
	public function getAll() : array;

	/**
	 * Provider의 이름을 반환합니다.
	 *
	 * @return string
	 */
	public function getName() : string;

	/**
	 * 모든 플레이어의 돈 데이터를 디스크에 저장합니다.
	 */
	public function save();

}
