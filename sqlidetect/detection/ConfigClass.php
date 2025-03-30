<?php
class ConfigClass {
    var $TB_AFTER;
    var $PB_AFTER; 
    var $RESET_AFTER;
    var $BLACK_LISTED;
    var $LAST_ATTACK_TIME;
    var $BLOCK_STATUS;
    var $RESET_COUNT;
    var $BLOCK_COUNT;
    var $BLACK_LIST_ID;

    function getInfo() {
        $qry = "SELECT * FROM SETTING_VALUES";
        $result = mysql_query($qry);
        while ($row = mysql_fetch_array($result)) {
            if($row["id"]==1) {
                $this->TB_AFTER = $row["settings_value"];
            } else if ($row["id"]==3) {
                $this->PB_AFTER = $row["settings_value"];
            } else if ($row["id"]==2) {
                $this->RESET_AFTER = $row["settings_value"];
            }
        }

        $qry = "SELECT * FROM BLACK_LIST WHERE ip = '".$_SERVER["REMOTE_ADDR"]."'";
        $result = mysql_query($qry);
        if(mysql_num_rows($result)==0) {
            $this->BLACK_LISTED = 'n';
            $this->BLOCK_COUNT = 0;
            $this->RESET_COUNT = 0;
            $this->BLOCK_STATUS = 1; // Never blocked
            $this->BLACK_LIST_ID = 0;
        } else {
            $this->BLACK_LISTED = 'y';
            while ($row = mysql_fetch_array($result)) {
                $this->BLACK_LIST_ID = $row["id"];
                $this->LAST_ATTACK_TIME = $row["last_attack_time"];
                $this->BLOCK_COUNT = $row["blk_count"];
                $this->RESET_COUNT = $row["reset_cnt"];
                $this->BLOCK_STATUS = $row["block_status"];
            }
        }
    }

    function allowed() {
        if($this->BLACK_LISTED == 'n') {
            return true;
        } else if ($this->BLOCK_STATUS==1 || $this->BLOCK_STATUS==3) {
            return true;
        } else if($this->BLOCK_STATUS==2) { // Temporarily blocked
            if(time() - strtotime($this->LAST_ATTACK_TIME) >= $this->RESET_AFTER) {
                $this->BLOCK_STATUS = 3; // Reset status
                $this->BLOCK_COUNT = 0;
                $this->RESET_COUNT++;
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    function change() {
        $this->LAST_ATTACK_TIME = time();
        if($this->BLOCK_STATUS == 1) { // Never blocked
            $this->BLOCK_COUNT++;
            if($this->BLOCK_COUNT >= $this->TB_AFTER) {
                $this->BLOCK_STATUS = 2; // Temporarily block
            }
        } else if($this->BLOCK_STATUS == 3) { // Reset status
            if($this->RESET_COUNT >= $this->PB_AFTER) {
                $this->BLOCK_STATUS = 4; // Permanent block
            } else {
                $this->BLOCK_COUNT++;
                if($this->BLOCK_COUNT >= $this->TB_AFTER) {
                    $this->BLOCK_STATUS = 2; // Temporarily block
                }
            }
        }
    }
}
?>