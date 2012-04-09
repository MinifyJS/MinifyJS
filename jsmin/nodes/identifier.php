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
		$this->linkedTo = $linkedTo;
		$this->id = self::$seq++;
	}

	public function mustDeclare() {
		$this->mustDeclare = true;
	}

	public function name() {
		return $this->name;
	}

	public function id() {
		return $this->id;
	}

	public function linkedTo() {
		return $this->linkedTo;
	}

	public function used($bool = null) {
		if ($bool === true) {
			$this->usage++;
		} elseif ($bool === false) {
			$this->usage--;
		} else {
			return $this->usage;
		}
	}

	public function small($new = null) {
		if ($new !== null) {
			if ($new === false) {
				$this->small = $this->name;
			} else {
				$this->small = $new;
			}
		} elseif (!$this->small && $this->mustDeclare && $this->scope->parent()) {
			if ($this->linkedTo) {
				$this->small = $this->linkedTo->small();
			} else {
				$this->small = $this->scope->gen($this);
			}
		}

		return $this->small;
	}

	public function toString() {
		if (AST::$finalize) {
		//	$this->small();
		}

		if (!$this->scope || !$this->scope->parent()) {
			return (string)$this->name;
		}

		return $this->small ? $this->small : $this->name;
	}
}