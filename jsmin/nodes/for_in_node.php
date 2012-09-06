<?php
class ForInNode extends Node {
	protected $iterator;
	protected $object;
	protected $body;

	public function __construct(Node $iterator, Expression $object, Node $body) {
		$this->iterator = $iterator;
		$this->object = $object;
		$this->body = $body;

		$this->iterator->write();

		parent::__construct();
	}

	public function visit(AST $ast) {
		$this->iterator = $this->iterator->visit($ast);
		$this->object = $this->object->visit($ast);
		$this->body = $this->body->visit($ast)->optimizeBreak();

		if ($this->body->isVoid()) {
			return $this->iterator;
		}

		return $this;
	}

	public function gone() {
		$this->iterator->gone();
		$this->iterator->unassign();

		$this->object->gone();
		$this->body->gone();
	}

	public function collectStatistics(AST $ast) {
		$this->iterator->collectStatistics($ast, true);
		$this->object->collectStatistics($ast);
		$this->body->collectStatistics($ast);
	}

	public function iterator($new = null) {
		if ($new) {
			$this->iterator = $new;
		}
		return $this->iterator;
	}

	public function last() {
		return $this->body->last();
	}

	public function countLetters(&$letters) {
		foreach(array('f', 'o', 'r', 'i', 'n') as $l) {
			$letters[$l] += 1;
		}

		$this->iterator->countLetters($letters);
		$this->object->countLetters($letters);
		$this->body->countLetters($letters);
	}

	public function toString() {
		return 'for('
			. Stream::legalEnd($this->iterator->toString())
			. 'in'
			. Stream::legalStart($this->object->toString())
		. ')' . $this->body->asBlock()->toString(null, true);
	}
}
