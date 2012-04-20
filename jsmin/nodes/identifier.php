<?php
/**
 * Identifier
 *
 */
class Identifier {
	protected $scope;
	protected $name;
	protected $small;
	protected $mustDeclare = false;

	protected $usage = 0;

	protected $linkedTo;

	public function __construct(Scope $scope = null, $name) {
		$this->scope = $scope;
		$this->name = $name;
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

	public function keep() {
		return $this->used() || !$this->scope->parent();
	}

	public function scope() {
		return $this->scope;
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
			echo $this->name . ' : ' . $this->used() . "\n";
			if ($new === false) {
				$this->small = $this->name;
			} else {
				$this->small = $new;
			}
		}

		return $this->small;
	}

	public function toString() {
		return $this->small ? $this->small : $this->name;
	}

	public static function isValid($str) {
		return preg_match('~\A(?:\\\\u[0-9A-F]{4}|[$_\pL\p{Nl}]+)+(?:\\\\u[0-9A-F]{4}|[$_\pL\pN\p{Mn}\p{Mc}\p{Pc}]+)*\z~i', $str)
			&& !Scope::reserved($str);
	}
}
