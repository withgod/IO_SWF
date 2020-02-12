<?php

require_once dirname(__FILE__).'/Bit.php';
require_once dirname(__FILE__).'/../Exception.php';

class IO_SWF_ABC_Code {
    var $codeData = null;
    var $codeArray = [];
    var $abc = null;
    var $instructionTable = [
        //         name             Arg type    Arg to pool
        0x1d => ["popscope"      , []            ],  // 29
        0x24 => ["pushbyte"      , ["ubyte"]     ],  // 36
        0x25 => ["pushshort"     , ["u30"]       ],  // 37
        0x30 => ["pushscope"     , []            ],  // 48
        0x41 => ["call"          , ["u30"]       ],  // 65
        0x47 => ["returnvoid"    , []            ],  // 71
        0x49 => ["construct"     , ["u30"]       ],  // 73
        0x4f => ["callproperty"  , ["u30","u30"] ],  // 79
        0x58 => ["newclass"      , ["u30"]       ],  // 88
        0x5d => ["findpropstrict", ["u30"]       ],  // 93
        0x60 => ["getlex"        , ["u30"]       , ["multiname"]],  // 96
        0x65 => ["getscopeobject", ["u30"]       ],  // 101 (u30?)
        0x66 => ["getproperty"   , ["u30"]       ],  // 102
        0x68 => ["initproperty"  , ["u30"]       ],  // 104
        0x86 => ["astype"        , ["u30"]       ],  // 134
        0x87 => ["astypelate"    , []            ],  // 135
        0x97 => ["bitnot"        , []            ],  // 151
        0xa0 => ["add"           , []            ],  // 160
        0xa8 => ["bitand"        , []            ],  // 168
        0xa9 => ["bitor"         , []            ],  // 169
        0xaa => ["bitxor"        , []            ],  // 170
        0xc5 => ["addi"          , []            ],  // 197
        0xd0 => ["getlocal_0"    , []            ],  // 208
        0xd1 => ["getlocal_1"    , []            ],  // 209
        0xd2 => ["getlocal_2"    , []            ],  // 210
        0xd3 => ["getlocal_3"    , []            ],  // 211
    ];
    function getInstructionEntry($n) {
        if (!isset($this->instructionTable[$n])) {
            throw new IO_SWF_Exception("unknown instruction:$n");
        }
        $entry = $this->instructionTable[$n];
        if (count($entry) < 2) {
            throw new IO_SWF_Exception("table broken instruction:$n");
        }
        return $entry;
    }
    function getInstructionName($n) {
        return $this->getInstructionEntry($n)[0];
    }
    function getInstructionArgsType($n) {
        return $this->getInstructionEntry($n)[1];
    }
    function getInstructionArgsPool($n) {
        $entry =  $this->getInstructionEntry($n);
        if (! isset($entry[2])) {
            return null;
        }
        return $entry[2];
    }
    function setABC($abc) {
        $this->abc = $abc;
    }
    function parse($codeData) {
        $this->codeData = $codeData;
        $bit = new IO_SWF_ABC_Bit();
        $bit->input($codeData);
        while ($bit->hasNextData(1)) {
            list($startOffset, $dummy) = $bit->getOffset();
            $inst = $bit->getUI8();
            $argsType = $this->getInstructionArgsType($inst);
            foreach ($argsType as $argType) {
                switch ($argType) {
                case "u30":
                    $bit->get_u30();
                    break;
                case "ubyte":
                    $bit->getUI8();
                    break;
                default:
                    throw new IO_SWF_Exception("unknown type$argType");
                    break;
                }
            }
            list($nextOffset, $dummy) = $bit->getOffset();
            $this->codeArray []= substr($codeData, $startOffset,
                                        $nextOffset - $startOffset);
        }
    }
    function dump() {
        $codeLength = strlen($this->codeData);
        $codeCount = count($this->codeArray);
        echo "    code(bytesize=$codeLength, nInst=$codeCount):\n";
        foreach ($this->codeArray as $idx => $codeSlice) {
            $bit = new IO_SWF_ABC_Bit();
            $bit->input($codeSlice);
            $inst = $bit->getUI8();
            $instName = $this->getInstructionName($inst);
            $argsType = $this->getInstructionArgsType($inst);
            $argsPool = $this->getInstructionArgsPool($inst);
            echo "        [$idx] $instName";
            foreach ($argsType as $i => $argType) {
                switch ($argType) {
                case "u30":
                    $v = $bit->get_u30();
                    break;
                case "ubyte":
                    $v = $bit->getUI8();
                    break;
                default:
                    throw new IO_SWF_Exception("unknown type$argType");
                    break;
                }
                echo " $v";
                if (isset($argsPool[$i])) {
                    $pool = $argsPool[$i];
                    switch ($pool) {
                    case "multiname":
                        $name = $this->abc->getMultiname_name($v);
                        echo "($name)";
                        break;
                    default:
                        throw new IO_SWF_Exception("Unknown pool keyword:$pool");
                    }
                }
            }
            echo "\n";
            $bit = null;
        }
    }
}
