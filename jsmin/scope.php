<?php
/**
 *
 */
class Scope {
	/**
	 * List based on occurence of characters in jQuery, Prototype and Mootools (all keywords and dot-identifiers)
	 */
	static private $all = array(
		'e','t','n','r','i','s','o','u','a','f','l','c','h','p','d','v','m','g','y','b','w','E','S','x','T','N',
		'C','k','L','A','O','M','_','D','P','H','B','j','F','I','q','R','U','z','W','X','V','$','J','K','Q','G',
		'Y','Z','0','5','1','6','3','7','2','9','8','4'
	);

	protected static $keywords = array(
		'abstract' => 1,
		'boolean' => 1, 'byte' => 1,
		'break' => 1, 'case' => 1, 'catch' => 1, 'continue' => 1, 'char' => 1, 'class' => 1, 'const' => 1,
		'default' => 1, 'delete' => 1, 'do' => 1, 'debugger' => 1, 'double' => 1,
		'else' => 1, 'enum' => 1, 'export' => 1, 'extends' => 1,
		'finally' => 1, 'for' => 1, 'function' => 1, 'final' => 1, 'float' => 1, 'false' => 1,
		'goto' => 1,
		'if' => 1, 'in' => 1, 'instanceof' => 1, 'implements' => 1, 'import' => 1, 'int' => 1, 'interface' => 1,
		'let' => 1, 'long' => 1,
		'new' => 1, 'native' => 1, 'null' => 1,
		'package' => 1, 'private' => 1, 'protected' => 1, 'public' => 1,
		'return' => 1,
		'switch' => 1, 'short' => 1, 'static' => 1, 'super' => 1, 'synchronized' => 1,
		'this' => 1, 'throw' => 1, 'try' => 1, 'typeof' => 1, 'throws' => 1, 'transient' => 1, 'true' => 1,
		'var' => 1, 'void' => 1, 'volatile' => 1,
		'while' => 1, 'with' => 1,
		'yield' => 1
	);

	protected static $reserved = array(
		// not reserved per se, but we'll still avoid them
		'arguments' => 1, 'eval' => 1, 'Infinity' => 1, 'NaN' => 1, 'undefined' => 1
	);

	protected $program;
	protected $parent;
	protected $labelScope;

	protected $usesWith = 0;

	protected $children = array();

	protected $nameIndex;

	protected $declared = array();

	// large - small
	protected $ls = array();
	// small - large
	protected $sl = array();

	protected $uses = array();

	public function __construct(AST $program, Scope $parent = null, $labelScope = false) {
		$this->program = $program;
		$this->parent = $parent;

		if ($parent) {
			$parent->add($this);
		}

		$this->labelScope = $labelScope;
	}

	public function usesWith($does = 1, $parents = false) {
		$this->usesWith += $does;

		foreach($this->children as $c) {
			$c->usesWith($does);
		}

		if ($parents) {
			$n = $this;
			while ($n = $n->parent) {
				$n->usesWith += $does;
			}
		}
	}

	public function optimize() {
		if (!AST::$options['mangle'] || $this->usesWith > 0) {
			return;
		}

		if ($this->parent || $this->labelScope) {
			uasort($this->declared, function (Identifier $a, Identifier $b) {
				return max(min($b->used() - $a->used(), 1), -1);
			});

			foreach($this->declared as $ident) {
				if (($ident->declared() && $ident->scope() === $this && $ident->keep(1)) || $this->labelScope) {
					for (;;) {
						$name = $this->base54($this->nameIndex++);

						if (isset(self::$reserved[$name]) || isset(self::$keywords[$name])) {
							continue;
						}

						if ($prev = $this->hasOptimized($name)) {
							// still need to check if this name is used here...
							$test = &$this->declared[$prev->sl[$name]];
							if (isset($test) && $test->scope() === $prev) {
								continue;
							}
						}

						$prev = $this->has($name);
						if ($prev && $prev !== $this && !$prev->hasOptimized($name)) {
							continue;
						}

						$ident->small($name);
						$this->sl[$name] = $ident->name();

						break;
					}
				}
			}
		}

		foreach($this->children as $child) {
			$child->optimize();
		}
	}

	protected function has($name) {
		for ($s = $this; $s; $s = $s->parent) {
			if (isset($s->declared[$name]) && $s->declared[$name]->scope() === $s && $s->declared[$name]->keep(1)) {
				return $s;
			}
		}
	}

	protected function hasOptimized($name) {
		for ($s = $this; $s; $s = $s->parent) {
			if (isset($s->sl[$name])) {
				return $s;
			}
		}
	}

	protected function add(Scope $child) {
		$this->children[] = $child;
	}

	public function parent() {
		return $this->parent;
	}

	public static function reserved($name) {
		return isset(self::$reserved[$name]) || isset(self::$keywords[$name]);
	}

	public static function keyword($name) {
		return isset(self::$keywords[$name]);
	}

	public function find($name, $declare = false) {
		if (!$declare) {
			if (!isset($this->declared[$name])) {
				if (!$this->parent) {
					$this->declared[$name] = new Identifier($this, $name);
				} else {
					$this->declared[$name] = $this->parent->find($name);
				}
			}
		} elseif (!isset($this->declared[$name])) {
			$this->declared[$name] = new Identifier($this, $name);
			$this->declared[$name]->mustDeclare();
		}

		return $this->declared[$name];
	}

	public function small($n) {
		if (isset($this->small[$n])) {
			return $this->small[$n];
		}
	}

	public function declared($name) {
		$n = $this;
		while ($n->parent) {
			$n = $n->parent;
		}

		return isset($n->declared[$name]);
	}

	public function gen($origName, $declare = true) {
		$n = new Identifier($this, $origName);

		if ($declare) {
			$n->mustDeclare();
		}

		$this->declared[$origName] = $n;

		return $n;
	}

	protected function base54($i) {
		$name = '';
		$base = 54;

		do {
			$name .= self::$all[$i % $base];
			$i = floor($i / $base);
			$base = 64;
		} while ($i > 0);

		return $name;
	}
}
