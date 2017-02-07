<?php namespace App\Service\RatchetClient;


class RatchetClient
{
	/**
	 * Хост сервера
	 * @var string
	 */
	protected $url;

	/**
	 * Порт сервера
	 * @var string
	 */
	protected $port;

	/**
	 * Цикл опроса
	 * @var \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|\React\EventLoop\StreamSelectLoop
	 */
	private $loop;

	/**
	 * @var
	 */
	protected $identifyKey;

	/**
	 * RatchetClient constructor.
	 *
	 * @param        $identifyKey
	 * @param string $url
	 * @param string $port
	 */
	public function __construct($identifyKey, $url = "ws://127.0.0.1", $port = "9000")
	{

		$this->identifyKey = $identifyKey;

		$this->url = $url;
		$this->port = $port;

		$this->loop = \React\EventLoop\Factory::create();
		$this->connector = new \Ratchet\Client\Connector($this->loop);
	}

	public function isAlive()
	{
		try
		{
			$this->send('alive');
			return true;
		}
		catch (\Exception $e)
		{
			return false;
		}
	}


	/**
	 * @param       $cmd
	 * @param array $params
	 *
	 * @return array
	 */
	public function send($cmd, $params = [])
	{
		$result = [];

		$loop = $this->loop;

		$identifyKey = $this->identifyKey;

		$connector = $this->connector;

		$connector($this->url . ':' . $this->port)
			->then(function(\Ratchet\Client\WebSocket $conn) use(&$result, $params, $cmd, $identifyKey, $loop)
			{
				$conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $msg) use ($conn, &$result, $params, $cmd, $identifyKey)
				{
					// ответ от сервера
					$result =json_decode($msg,true);
					$conn->close();
				});

				$conn->send($identifyKey. ':' . $cmd . ':'. implode(':' ,$params));

			}, function(\Exception $e) use ($loop) {
				$loop->stop();
				return false;
			});

		$loop->run();

		return $result;
	}
}