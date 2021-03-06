<?php
/**
 * Identifier
 *
 */
class Identifier {
	protected static $sequence = 0;

	protected $scope;
	protected $name;
	protected $small;

	protected $mustDeclare = false;

	protected $reassigned = 0;
	protected $initializer;

	protected $id;

	protected $usage = 0;

	protected $toString;

	public function __construct(Scope $scope = null, $name) {
		$this->scope = $scope;
		$this->name = $name;

		$this->toString = $this->escape($this->name);

		$this->id = self::$sequence++;
	}

	public function cleanStats() {
		$this->reassigned = 0;
		$this->usage = 0;
		$this->initializer = null;
	}

	public function mustDeclare() {
		$this->mustDeclare = true;
	}

	public function declared() {
		return $this->mustDeclare;
	}

	public function reassigned($bool = null) {
		if ($bool) {
			++$this->reassigned;
		} elseif ($bool === false) {
			--$this->reassigned;
		}

		return $this->reassigned > 0;
	}

	public function initializer(Expression $e = null) {
		if ($e) {
			$this->initializer = $e;
		}

		if (!$this->reassigned()) {
			return $this->initializer;
		}
	}

	public function isLocal() {
		return $this->scope->parent() !== null;
	}

	public function name() {
		return $this->name;
	}

	public function keep($min = 0) {
		return !$this->scope || $this->used() > $min || $this->reassigned() || !$this->scope->parent();
	}

	public function scope() {
		return $this->scope;
	}

	public function used($bool = null) {
		if ($bool === true) {
			$this->usage++;
		} elseif ($bool === false && $this->usage > 0) {
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

			$this->toString = $this->escape($this->small ?: $this->name);
		}

		return $this->small;
	}

	public function toString() {
		if ($this->scope && AST::$options['squeeze']) {
			return 'a';
		}

		return $this->toString;

		//return $this->escape($this->small ? $this->small : $this->name);
	}

	public function escape($name) {
		if (self::isValid($name)) {
			return $name;
		}

		return (mb_strlen($name, 'UTF-8') > 1 ? substr($name, 0, -1) : '') . '\\u' . substr('0000' . dechex(ord(substr($name, -1))), -4);
	}

	public static function isValid($str) {
		return preg_match('~\A(?:\\\\u[0-9A-F]{4}|[$_\pL\p{Nl}\x{200c}\x{200d}]+)+(?:\\\\u[0-9A-F]{4}|[$_\pL\pN\p{Mn}\p{Mc}\p{Pc}\x{200c}\x{200d}]+)*\z~iu', $str)
			&& !Scope::keyword($str);
	}
}
