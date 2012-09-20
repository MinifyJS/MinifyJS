<?php
class SwitchNode extends Node {
	protected $discriminant;
	protected $cases = array();

	public function __construct(Expression $discriminant, array $cases) {
		$this->discriminant = $discriminant;
		$this->cases = $cases;

		parent::__construct();
	}

	public function visit(AST $ast, Node $parent = null) {
		$this->discriminant = $this->discriminant->visit($ast, $this);

		foreach($this->cases as $i => $e) {
			$this->cases[$i] = $e->visit($ast, $this);
		}

		if ($this->cases && $last = end($this->cases)) {
			$last->removeBreak();
		}

		return $this;
	}

	public function gone() {
		$this->discriminant->gone();

		foreach($this->cases as $c) {
			$c->gone();
		}
	}

	public function collectStatistics(AST $ast) {
		$this->discriminant->collectStatistics($ast);

		foreach($this->cases as $i => $e) {
			$e->collectStatistics($ast);
		}
	}

	public function countLetters(&$letters) {
		$letters['s'] += 1;
		$letters['w'] += 1;
		$letters['i'] += 1;
		$letters['t'] += 1;
		$letters['c'] += 1;
		$letters['h'] += 1;

		$this->discriminant->countLetters($letters);

		foreach ($this->cases as $c) {
			$c->countLetters($letters);
		}
	}

	public function toString() {
		$result = 'switch(' . $this->discriminant->toString() . ')';
		if (AST::$options['beautify']) {
			$result .= ' {' . "\n";
		} else {
			$result .= '{';
		}

		$semi = '';

		foreach($this->cases as $case) {
			$result .= $semi . $case->toString();
			$semi = substr(rtrim($result), -1) === ';' ? '' : ';';
		}

		$result .= '}';

		return $result;
	}
}