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

	protected $reassigned = 0;
	protected $initializer;

	protected $usage = 0;

	protected $linkedTo;

	protected $toString;

	public function __construct(Scope $scope = null, $name) {
		$this->scope = $scope;
		$this->name = $name;

		$this->toString = $this->escape($this->name);
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
		return $this->used() > $min || $this->reassigned() || !$this->scope->parent();
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
		if (AST::$options['squeeze']) {
			return 'ab';
		}

		return $this->toString;

		//return $this->escape($this->small ? $this->small : $this->name);
	}

	public function escape($name) {
		if (self::isValid($name)) {
			return $name;
		}

		return '\\u' . substr('0000' . dechex(ord($name[0])), -4) . substr($name, 1);
	}

	public static function isValid($str) {
		return preg_match('~\A(?:\\\\u[0-9A-F]{4}|[$_\pL\p{Nl}]+)+(?:\\\\u[0-9A-F]{4}|[$_\pL\pN\p{Mn}\p{Mc}\p{Pc}]+)*\z~i', $str)
			&& !Scope::keyword($str);
	}
}
