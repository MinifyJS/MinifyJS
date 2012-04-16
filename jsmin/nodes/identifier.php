<?php
/**
 * Identifier
 *
 */
class Identifier {
	static private $seq = 0;

	protected $scope;
	protected $name;
	protected $small;
	protected $mustDeclare = false;

	protected $usage = 0;

	protected $linkedTo;

	private $id;

	public function __construct(Scope $scope = null, $name, Identifier $linkedTo = null) {
		$this->scope = $scope;
		$this->name = $name;
		//$this->linkedTo = $linkedTo;
		$this->id = self::$seq++;
	}

	public function mustDeclare() {
		$this->mustDeclare = true;
	}

	public function declared() {
		return $this->mustDeclare;
	}

	public function name() {
		return $this->name;
	}

	public function id() {
		return $this->id;
	}

	public function keep() {
		return $this->used() || !$this->scope->parent();
	}

	public function scope() {
		return $this->scope;
	}

	public function linkedTo() {
		return $this->linkedTo;
	}

	public function used($bool = null) {
		if ($bool === true) {
			$this->usage++;
		} elseif ($bool === false) {
			$this->usage--;
		}

		return $this->usage;
	}

	public function small($new = null) {
		if ($new !== null) {
			if ($new === false) {
				$this->small = $this->name;
			} else {
				$this->small = $new;
			}
		}

		return $this->small;
	}

	public function toString() {
		if (!$this->scope || !$this->scope->parent()) {
			return (string)$this->name;
		}

		return $this->small ? $this->small : $this->name;
	}

	public static function isValid($str) {
		return preg_match('~\A(?:\\\\u[0-9A-F]{4}|[$_\pL\p{Nl}]+)+(?:\\\\u[0-9A-F]{4}|[$_\pL\pN\p{Mn}\p{Mc}\p{Pc}]+)*\z~i', $str)
			&& !Scope::reserved($str);
	}
}
