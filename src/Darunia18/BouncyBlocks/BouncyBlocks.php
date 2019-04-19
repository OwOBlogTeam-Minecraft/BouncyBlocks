<?php

/*                             Copyright (c) 2017-2018 TeaTech All right Reserved.
 *
 *      ████████████  ██████████           ██         ████████  ██           ██████████    ██          ██
 *           ██       ██                 ██  ██       ██        ██          ██        ██   ████        ██
 *           ██       ██                ██    ██      ██        ██          ██        ██   ██  ██      ██
 *           ██       ██████████       ██      ██     ██        ██          ██        ██   ██    ██    ██
 *           ██       ██              ████████████    ██        ██          ██        ██   ██      ██  ██
 *           ██       ██             ██          ██   ██        ██          ██        ██   ██        ████
 *           ██       ██████████    ██            ██  ████████  ██████████   ██████████    ██          ██
**/

namespace Darunia18\BouncyBlocks;

use pocketmine\Player;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;

use pocketmine\block\Block;
use pocketmine\math\Vector3;

class BouncyBlocks extends \pocketmine\plugin\PluginBase implements \pocketmine\event\Listener
{
	# This sets the maximum height a player can bounce.
	# 1=10 blocks from the trampoline, 2=25 blocks from the trampoline, and 3=40 blocks from the trampoline
	private $max = 3;
	private $blocks = 
	[
		Block::BED_BLOCK,            // id-26
		Block::BROWN_MUSHROOM_BLOCK, // id-99
		Block::RED_MUSHROOM_BLOCK,   // id-100
	];
	
	public $fall;
	public $bounceVelocity;
	public $userStatus;
	
	public function onEnable()
	{
		$this->fall           = new \SplObjectStorage();
		$this->bounceVelocity = new \SplObjectStorage();
		$this->userStatus     = new \SplObjectStorage();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info("§a本插件已通过PM开发者§bTeaclon§f(§e锤子§f)§a兼容至 §6PM v1.4.x §a及更高游戏版本.");
		$this->getLogger()->info("§e原作者: §bDarunia18");
		$this->getLogger()->info("§eGitHub开源地址: §2https://github.com/Darunia18/BouncyBlocks");
		$this->getLogger()->info("§c注意: 本插件仅对源代码的§e缩进格式§c以及§e原本不兼容的API进行调整§f/§e更改§c, §l并没有修改原作者的版权§r§c, 请其他开发者注意此事项.");
		$this->getLogger()->info("目前可以进行跳蹦功能的方块ID有: ".implode(", ", $this->blocks));
	}
	
	
	
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool
	{
		switch($command->getName())
		{
			default:
				return false;
			break;
				
			case "蹦床":
			case "bounce":
				if(!$sender instanceof Player)
				{
					$sender->sendMessage("请在游戏内使用这个指令.");
					return true;
				}
				if(!isset($args[0]))
				{
					$sender->sendMessage("使用命令: /bounce <on|off>");
					return true;
				}
				switch($args[0])
				{
					default:
						$sender->sendMessage("使用命令: /bounce <on|off>");
						return true;
					break;
					
					case "on":
						$this->userStatus->attach($sender);
						$sender->sendMessage("你已经开启了蹦床功能, 快去玩吧~");
						return true;
					break;
					
					case "off":
						$this->userStatus->detach($sender);
						$sender->sendMessage("你关闭了蹦床功能~");
						return true;
					break;
				}
				return true;
			break;
				
			case "bblock":
				if(!isset($args[0]))
				{
					$sender->sendMessage("目前可以进行跳蹦功能的方块ID有: ".implode(", ", $this->blocks));
					$sender->sendMessage("使用命令: /bblock <blockId>   添加或删除一个方块进行跳蹦");
					return true;
				}
				if((Block::get($args[0]) instanceof \pocketmine\block\Air) || (Block::get($args[0]) instanceof \pocketmine\block\UnknownBlock))
				{
					$sender->sendMessage("方块ID无效. 请输入一个有效的方块ID.");
					return true;
				}
				if(in_array($args[0], $this->blocks))
				{
					unset($this->blocks[array_search($args[0], $this->blocks)]);
					$sender->sendMessage("成功在允许跳蹦的方块列表中§c删除§f方块ID #{$args[0]} . 本次操作不会保存数据.");
				}
				else
				{
					$this->blocks[] = $args[0];
					$sender->sendMessage("成功添加方块ID #{$args[0]} 至§a允许§f跳蹦的方块列表内. 本次操作不会保存数据.");
				}
				return true;
			break;
		}
	}
	
	public function onEntityDamage(EntityDamageEvent $event)
	{
		if($event->getEntity() instanceof Player)
		{
			$player = $event->getEntity();
			
			if((isset($this->fall[$player]) && ($event->getCause() == 4)) || $player->isOp())
			{
				$event->setCancelled();
			}
		}
	}
	
	public function onPlayerMove(PlayerMoveEvent $event)
	{
		$player = $event->getPlayer();
		
		if(!isset($this->userStatus[$player]))
		{
			$block = $player->getLevel()->getBlockIdAt($player->x, ($player->y -0.1), $player->z);
			
			if($block != 0 && in_array($block, $this->blocks)){
				
				if(!isset($this->bounceVelocity[$player]) || ($this->bounceVelocity[$player] == 0.0))
				{
					$this->bounceVelocity[$player] = ($player->getMotion()->getY() + 0.2);
				}
				
				if($this->bounceVelocity[$player] <= $this->max)
				{
					$this->bounceVelocity[$player] = ($this->bounceVelocity[$player] + 0.2);
				}
				
				$this->fall->attach($player);
				$motion    = new Vector3($player->getMotion()->x, $player->getMotion()->y, $player->getMotion()->z);
				$motion->y = $this->bounceVelocity[$player];
				$player->setMotion($motion);
			}
			
			if(isset($this->fall[$player]))
			{
				if(!$block == 0 && !in_array($block, $this->blocks))
				{
					$this->fall->detach($player);
					$this->bounceVelocity[$player] = 0.0;
				}
			}
		}
	}
}
?>