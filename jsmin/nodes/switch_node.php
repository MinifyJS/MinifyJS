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
		$discriminant = $this->discriminant->visit($ast, $this);
		$cases = array();

		foreach($this->cases as $case) {
			$cases[] = $case->visit($ast, $this);
		}

		if ($cases && $last = end($cases)) {
			$last->removeBreak();
		}

		return new SwitchNode($discriminant, $cases);
	}

	public function gone() {
		$this->discriminant->gone();

		foreach($this->cases as $c) {
			$c->gone();
		}
	}

	public function moveExpression(Expression $e) {
		$this->discriminant = new CommaExpression(array_merge(
			$e->nodes(), $this->discriminant->nodes()
		));
		return true;
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
			$last = substr(rtrim($result), -1);
			$semi = $last === ';' || $last === ':' ? '' : ';';
		}

		$result .= '}';

		return $result;
	}
}
