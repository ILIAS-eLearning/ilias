<?php

/**
 * @author JKN Inc.
 * @copyright 2015
 */

include_once('./Services/Database/classes/class.ilDB.php');

class ilLPRubricCard
{
    protected $db;
    protected $obj_id;
    
    private $rubric_id;
    private $passing_grade=50;
    
    public function __construct($obj_id)
    {
        global $ilDB;
        
        $this->ilDB=$ilDB;
        $this->obj_id=$obj_id;
        
    }
    
    /*
    public function setRubricId($rubric_id)
    {
        $this->rubric_id=$rubric_id;        
    } 
    */   
    
    public function getPassingGrade()
    {
        return($this->passing_grade);    
    }
    
    private function incrementSequence($table)
    {
        $sequence="";
        
        //update and get the next sequence
        $this->ilDB->manipulate("update $table set sequence=sequence+1");
        
        //what is the current sequence
        $set=$this->ilDB->query("select sequence from $table");
        $row=$this->ilDB->fetchAssoc($set);
        $sequence=$row['sequence'];
        
        if(empty($sequence)){
            $this->ilDB->manipulate("insert into $table (sequence) value (1)");
            $sequence=1;
        }
        
        return($sequence);
    }
    
    private function getCardPostData()
    {
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form=new ilPropertyFormGUI();
                
        // gather point values
        $points=array();        
        for($a=0;$a<7;$a++){
            $tmp_point=$form->getInput("Points$a",false);
            $tmp_label=$form->getInput("Label$a",false);
            if(is_numeric($tmp_point)&&!empty($tmp_label)){
            //if(is_integer($tmp_point)&&is_string($tmp_label)){
                array_push($points,array('weight'=>$tmp_point,'label'=>$tmp_label));                
            }                                    
        }
        
        // gather passing grade
        $this->passing_grade=$form->getInput('passing_grade',false);
        
        // gather group, critiera, behavior
        $g=1;// set group increment
        $groups=array();
        $tmp_group=$form->getInput("Group_${g}",false);
        while(!empty($tmp_group)){
            
            $c=1;// set criteria increment            
            $groups[$tmp_group]=array();
            
            $tmp_criteria=$form->getInput("Criteria_${g}_${c}",false);
            
            do{
                
                // set array for criteria behaviors
                $groups[$tmp_group][$tmp_criteria]=array();
                
                for($b=1;$b<=count($points);$b++){
                    $groups[$tmp_group][$tmp_criteria][$b]=$form->getInput("Behavior_${g}_${c}_${b}",false);
                }
                
                $c++;
                
                $tmp_criteria=$form->getInput("Criteria_${g}_${c}",false);
                
            }while(!empty($tmp_criteria));
            
            $g++;
            
            $tmp_group=$form->getInput("Group_${g}",false);
        }
        
        return(array('groups'=>$groups,'points'=>$points));
        
    }
    
        
    private function getGradePostData($rubric_data)
    {
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form=new ilPropertyFormGUI();
        
        $grades=array();
        
        foreach($rubric_data['groups'] as $g => $group){
            
            foreach($group['criteria'] as $c => $criteria){
                
                $grades[$criteria['criteria_id']]['behavior_id']=$form->getInput("Criteria_${g}_${c}",false);
                $grades[$criteria['criteria_id']]['comment']=$form->getInput("Comment_${g}_${c}",false);
                
                foreach($criteria['behaviors'] as $b => $behavior){
                    if($behavior['behavior_id']==$grades[$criteria['criteria_id']]['behavior_id']){
                        $grades[$criteria['criteria_id']]['label_id']=$behavior['rubric_label_id'];
                    }
                } 
                
            }
            
        }
        return($grades);
    }
    
    private function getGradeUserId()
    {
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form=new ilPropertyFormGUI();
        
        return($form->getInput('user_id',false));
    }    
    
    public function grade($rubric_data)
    {
        $grades=$this->getGradePostData($rubric_data);
        $user_id=$this->getGradeUserId();
        
        // null out grades
        $this->ilDB->manipulate("update rubric_data set deleted = NOW() where rubric_id=".$this->ilDB->quote($this->rubric_id, "integer")." and usr_id=".$this->ilDB->quote($user_id, "integer"));
        
        $count=0;
        foreach($grades as $criteria_id => $grade){
            
            // does this grade already exist?
            $set=$this->ilDB->query(
                    "select 
                        rubric_data_id 
                     from rubric_data 
                     where 
                        rubric_id=".$this->ilDB->quote($this->rubric_id, "integer")." and 
                        usr_id=".$this->ilDB->quote($user_id, "integer")." and 
                        rubric_behavior_id=".$this->ilDB->quote($grade['behavior_id'], "integer")
            );
            $row=$this->ilDB->fetchAssoc($set);
            if(!empty($row)){
                //update
                $this->ilDB->manipulate(
                    "update rubric_data set 
                        rubric_label_id=".$this->ilDB->quote($grade['label_id'], "integer").",
                        behavior_comment=".$this->ilDB->quote($grade['comment'], "text").",
                        deleted=NULL,
                        last_update=NOW(),
                        owner=".$this->ilDB->quote($_SESSION['AccountId'], "integer")." 
                    where rubric_data_id=".$this->ilDB->quote($row['rubric_data_id'], "integer")
                );
            }else{
                //new record, insert
                $new_rubric_data_id=$this->incrementSequence('rubric_data_seq');
            
                $this->ilDB->manipulate(
                    "insert into rubric_data (rubric_data_id,rubric_id,usr_id,rubric_behavior_id,rubric_label_id,behavior_comment,owner,create_date,last_update) values (
                        ".$this->ilDB->quote($new_rubric_data_id, "integer").",                    
                        ".$this->ilDB->quote($this->rubric_id, "integer").",
                        ".$this->ilDB->quote($user_id, "integer").",
                        ".$this->ilDB->quote($grade['behavior_id'], "integer").",
                        ".$this->ilDB->quote($grade['label_id'], "integer").",
                        ".$this->ilDB->quote($grade['comment'], "text").",
                        ".$this->ilDB->quote($_SESSION['AccountId'], "integer").",                    
                        NOW(),
                        NOW()
                        
                    )"
                );
                
            }
            
            $count++;
                        
        }
    }
    
    public function save()
    {
        $data=$this->getCardPostData();
        
        $this->saveRubricCardTbl();
        
        $labels=$this->saveRubricLabelTbl($data['points']);
        
        $this->saveRubricGroupTbl($data['groups']);
        
        $this->saveRubricCriteriaTbl($data['groups']);
        
        $this->saveRubricBehaviorTbl($data['groups'],$labels);
        
    }
    
    public function getRubricUserGradeData($user_id)
    {
        $data=array();
        
        $res=$this->ilDB->query(
            "select 
                rubric_behavior_id,rubric_label_id,behavior_comment 
             from rubric_data 
             where 
                deleted is null and 
                rubric_id=".$this->ilDB->quote($this->rubric_id, "integer")." and 
                usr_id=".$this->ilDB->quote($user_id, "integer")
        );
        while($row=$res->fetchRow(DB_FETCHMODE_OBJECT)){
            array_push($data,array(
                'rubric_behavior_id'=>$row->rubric_behavior_id,
                'rubric_label_id'=>$row->rubric_label_id,
                'behavior_comment'=>$row->behavior_comment,
            ));
        }
        
        return($data);
        
    }
    
    public function load()
    {    
        $data=array();
        $data['groups']=$this->getRubricGroups();
        $data['labels']=$this->getRubricLabels();
        
        return($data);
    }
    
    public function objHasRubric()
    {
        $res=$this->ilDB->query(
            "select rubric_id,passing_grade from rubric where obj_id=".$this->ilDB->quote($this->obj_id, "integer")." and deleted is null"
        );
        $row=$res->fetchRow(DB_FETCHMODE_OBJECT);
        if(!empty($row->rubric_id)){
            $this->rubric_id=$row->rubric_id;
            $this->passing_grade=$row->passing_grade;
            return(true);
        }else{
            return(false);
        }
        
    }
    
    private function getRubricGroups()
    {
        $data=array();
        
        $res=$this->ilDB->query(
            'select rubric_group_id,group_name 
            from rubric_group 
            where
                rubric_id='.$this->ilDB->quote($this->rubric_id, "integer").' and 
                deleted is null'
        );
        
        while($row=$res->fetchRow(DB_FETCHMODE_OBJECT)){
            
            array_push($data,array(
                'group_id'=>$row->rubric_group_id,
                'group_name'=>$row->group_name,
                'criteria'=>$this->getRubricCriteriaByGroupId($row->rubric_group_id),                
            ));
            
        }
        
        return($data);
    }
    
    private function getRubricCriteriaByGroupId($rubric_group_id)
    {
        $data=array();
        
        $res=$this->ilDB->query('select rubric_criteria_id,criteria 
                                        from rubric_criteria
                                        where
                                            rubric_group_id='.$this->ilDB->quote($rubric_group_id, "integer").' and 
                                            deleted is null
        ');
        while($row=$res->fetchRow(DB_FETCHMODE_OBJECT)){
            
            array_push($data,array(
                'criteria_id'=>$row->rubric_criteria_id,
                'criteria'=>$row->criteria,
                'behaviors'=>$this->getRubricBehaviorByCriteriaId($row->rubric_criteria_id),
            ));
            
        }
        
        return($data);
        
    }
    
    private function getRubricBehaviorByCriteriaId($rubric_criteria_id)
    {
        $data=array();
        
        $res=$this->ilDB->query('select b.rubric_behavior_id,b.description,b.rubric_label_id 
                                        from rubric_behavior b
                                            inner join rubric_label l on b.rubric_label_id=l.rubric_label_id
                                        where
                                            b.rubric_criteria_id='.$this->ilDB->quote($rubric_criteria_id, "integer").' and 
                                            b.deleted is null
                                        order by l.sort_order
        ');
        
        while($row=$res->fetchRow(DB_FETCHMODE_OBJECT)){
            array_push($data,array(
                'behavior_id'=>$row->rubric_behavior_id,
                'description'=>$row->description,
                'rubric_label_id'=>$row->rubric_label_id,
            ));
        }
        
        return($data);
    }
    
    private function getRubricLabels()
    {
        $data=array();
        
        $res=$this->ilDB->query("select rubric_label_id,label,weight,create_date,last_update from rubric_label where deleted is null and rubric_id=".$this->ilDB->quote($this->rubric_id, "integer")." order by sort_order");
        while($row=$res->fetchRow(DB_FETCHMODE_OBJECT)){
            array_push($data,array(
                'rubric_label_id'=>$row->rubric_label_id,
                'label'=>$row->label,
                'weight'=>$row->weight,                
                'create_date'=>$row->create_date,
                'last_update'=>$row->last_update,
            ));
        }
        
        return($data);
    }
    
    
    private function saveRubricBehaviorTbl($data,$labels)
    {
        //null out behaviors
        $this->ilDB->manipulate(
            "update rubric_behavior as b 
                inner join rubric_criteria as c on b.rubric_criteria_id=c.rubric_criteria_id
                inner join rubric_group as g on c.rubric_group_id=g.rubric_group_id
             set 
                b.deleted=NOW()
             where
                g.rubric_id=".$this->ilDB->quote($this->rubric_id, "integer")
        );
        
        // insert or update behaviors
        foreach($data as $new_group_name => $new_criteria_data){
            
            //new criteria
            foreach($new_criteria_data as $new_criteria_name => $new_behavior_data){
                
                foreach($new_behavior_data as $k => $behavior_name){
                    //does this new behavior already exist for this criteria?
                    $set=$this->ilDB->query(
                        "select 
                            c.rubric_criteria_id,b.rubric_behavior_id,b.rubric_label_id 
                         from rubric_behavior b 
                            inner join rubric_criteria c on b.rubric_criteria_id=c.rubric_criteria_id 
                         where 
                            c.deleted is null and
                            b.description=".$this->ilDB->quote($behavior_name, "text")." and
                            c.criteria=".$this->ilDB->quote($new_criteria_name, "text")
                    );
                    $row=$this->ilDB->fetchAssoc($set);
                    
                    //if this behavior exists, check to see if it is the correct label
                    $is_new_behavior=true;
                    if(count($row)>0){
                        //does this new behavior already exist for this label (weight)
                        if($row['rubric_label_id']==$labels[($k-1)]['rubric_label_id']){
                            //not a new behavior, update deleted to not null
                            $this->ilDB->manipulate("update rubric_behavior set deleted=null where rubric_behavior_id=".$this->ilDB->quote($row['rubric_behavior_id'], "integer"));
                            $is_new_behavior=false;
                        }
                        
                    }
                    
                    if($is_new_behavior===true){
                        
                        $new_rubric_behavior_id=$this->incrementSequence('rubric_behavior_seq');
                        
                        // get the group id 
                        $set=$this->ilDB->query(
                            "select 
                                c.rubric_criteria_id 
                            from rubric_criteria c
                                inner join rubric_group g on c.rubric_group_id=g.rubric_group_id
                            where 
                                c.deleted is null and 
                                g.rubric_id= ".$this->ilDB->quote($this->rubric_id, "integer")." and 
                                c.criteria=".$this->ilDB->quote($new_criteria_name, "text")
                        );
                        $row_criteria=$this->ilDB->fetchAssoc($set);
                        
                        $this->ilDB->manipulate(
                            "insert into rubric_behavior (rubric_behavior_id,rubric_criteria_id,rubric_label_id,description,owner,create_date,last_update) values (
                                ".$this->ilDB->quote($new_rubric_behavior_id, "integer").",
                                ".$this->ilDB->quote($row_criteria['rubric_criteria_id'], "integer").",
                                ".$this->ilDB->quote($labels[($k-1)]['rubric_label_id'], "integer").",
                                ".$this->ilDB->quote($behavior_name, "text").",
                                ".$this->ilDB->quote($_SESSION['AccountId'], "integer").",
                                NOW(),
                                NOW()                                
                            )"
                        );
                    }                    
                    
                }
                
            }
            
        }
    }
    
    private function saveRubricCriteriaTbl($data)
    {
        // null out criteria
        $this->ilDB->manipulate("update rubric_criteria as c inner join rubric_group as g on c.rubric_group_id=g.rubric_group_id set c.deleted = NOW() where g.rubric_id=".$this->ilDB->quote($this->rubric_id, "integer"));
        
        // insert or update the criteria
        //new data        
        foreach($data as $new_group_name => $new_criteria_data){
            
            //new criteria
            foreach($new_criteria_data as $new_criteria_name => $new_behavior_data){
                
                //does this new criteria already exist for this group?
                $set=$this->ilDB->query(
                    "select 
                        g.rubric_group_id,c.rubric_criteria_id 
                     from rubric_group g 
                        inner join rubric_criteria c on g.rubric_group_id=c.rubric_group_id 
                     where 
                        g.deleted is null and
                        criteria=".$this->ilDB->quote($new_criteria_name, "text")." and
                        g.group_name=".$this->ilDB->quote($new_group_name, "text")
                );
                $row=$this->ilDB->fetchAssoc($set);
                
                if(count($row)>0){
                    
                    //exists, undelete
                    $this->ilDB->manipulate("update rubric_criteria set deleted=null where rubric_criteria_id=".$this->ilDB->quote($row['rubric_criteria_id'], "integer"));
                    
                }else{
                    //doesn't exist, insert
                    $new_rubric_criteria_id=$this->incrementSequence('rubric_criteria_seq');
                    
                    // get the group id 
                    $set=$this->ilDB->query(
                        "select 
                            rubric_group_id 
                        from rubric_group 
                        where 
                            deleted is null and 
                            rubric_id= ".$this->ilDB->quote($this->rubric_id, "integer")." and 
                            group_name=".$this->ilDB->quote($new_group_name, "text")
                    );
                    $row_group=$this->ilDB->fetchAssoc($set);
                    
                    $this->ilDB->manipulate(
                        "insert into rubric_criteria (rubric_criteria_id,rubric_group_id,criteria,owner,create_date,last_update) values (
                            ".$this->ilDB->quote($new_rubric_criteria_id, "integer").",
                            ".$this->ilDB->quote($row_group['rubric_group_id'], "integer").",
                            ".$this->ilDB->quote($new_criteria_name, "text").",
                            ".$this->ilDB->quote($_SESSION['AccountId'], "integer").",
                            NOW(),
                            NOW()
                        )"
                    );
                }
                
            }// foreach criteria_data
            
        }// foreach data
    }
    
    private function saveRubricGroupTbl($data)
    {
        /**
         *      Add/Update Groups
         */
        //$active_group_id=array(); 
        //get the current active groups
        $current_groups=array();
        $set=$this->ilDB->query("select rubric_group_id,group_name from rubric_group where deleted is null and rubric_id=".$this->ilDB->quote($this->rubric_id, "integer"));
        while($row=$this->ilDB->fetchAssoc($set)){
            $current_groups[$row['rubric_group_id']]=$row['group_name'];
        }
        
        // null out groups
        $this->ilDB->manipulate("update rubric_group set deleted=NOW() where deleted is null and rubric_id=".$this->ilDB->quote($this->rubric_id, "integer"));
        
        // insert or update the groups
        
        foreach($data as $new_group_name => $criteria_data){
            
            $is_new_group=true;
            // does this group already exist
            foreach($current_groups as $rubric_group_id => $current_group_name){
                if($new_group_name==$current_group_name){
                    
                    $this->ilDB->manipulate("update rubric_group set deleted=null where rubric_group_id=".$this->ilDB->quote($rubric_group_id, "integer"));
                    
                    $is_new_group=false;
                    
                }
                
            }
            
            if($is_new_group===true){
                
                $new_rubric_group_id=$this->incrementSequence('rubric_group_seq');
                
                $this->ilDB->manipulate(
                    "insert into rubric_group (rubric_group_id,rubric_id,group_name,owner,create_date,last_update) values (
                        ".$this->ilDB->quote($new_rubric_group_id, "integer").",
                        ".$this->ilDB->quote($this->rubric_id, "integer").",
                        ".$this->ilDB->quote($new_group_name, "text").",
                        ".$this->ilDB->quote($_SESSION['AccountId'], "integer").",
                        NOW(),
                        NOW()
                    )"
                );
                
            }
            
            
        }// foreach data
    }
    
    private function saveRubricLabelTbl($labels)
    {
        /**
         *      Add/Update Rubric Labels
         */
         
        //get the current active labels
        $current_labels=array();
        $set=$this->ilDB->query("select rubric_label_id,label,weight from rubric_label where deleted is null and rubric_id=".$this->ilDB->quote($this->rubric_id, "integer"));
        while($row=$this->ilDB->fetchAssoc($set)){
            $current_labels[$row['rubric_label_id']]=array('label'=>$row['label'],'weight'=>$row['weight']);
        }
        
        // null out labels
        $this->ilDB->manipulate("update rubric_label set deleted=NOW() where deleted is null and rubric_id=".$this->ilDB->quote($this->rubric_id, "integer"));
        
        $sort_order=0;
        foreach($labels as $k => $new_label){
                        
            //does this label already exist?
            $is_new_label=true;
            
            
                        
            foreach($current_labels as $rubric_label_id => $compare_label){
                
                if($compare_label['label']==$new_label['label']&&$compare_label['weight']==$new_label['weight']){
                    
                    //nothing has changed, remove deleted status
                    $this->ilDB->manipulate("update rubric_label set deleted=null, sort_order=".$this->ilDB->quote($sort_order, "integer")." where rubric_label_id=".$this->ilDB->quote($rubric_label_id, "integer"));
                    
                    $is_new_label=false;
                    
                    $labels[$k]['rubric_label_id']=$rubric_label_id;
                    
                }/*elseif($compare_label['label']==$new_label['label']||$compare_label['weight']==$new_label['weight']){
                    
                    //label or points is the same, update date and remove deleted status
                    $this->ilDB->manipulate(
                        "update rubric_label set 
                            deleted=null,
                            label=".$this->ilDB->quote($new_label['label'], "text").",
                            weight=".$this->ilDB->quote(number_format($new_label['weight'],2), "text").",
                            owner=".$this->ilDB->quote($_SESSION['AccountId'], "integer").",
                            last_update=NOW() 
                        where 
                            rubric_label_id=".$this->ilDB->quote($rubric_label_id, "integer")
                    );
                    
                    $is_new_label=false;
                    
                    $labels[$k]['rubric_label_id']=$rubric_label_id;
                                    
                }*/
                
            }// foreach current_labels
            
            if($is_new_label===true){
                $new_rubric_label_id=$this->incrementSequence('rubric_label_seq');
                
                // now add it in
                $this->ilDB->manipulate(
                    "insert into rubric_label (rubric_label_id,rubric_id,label,weight,sort_order,owner,create_date,last_update) 
                    values (
                        ".$this->ilDB->quote($new_rubric_label_id, "integer").",
                        ".$this->ilDB->quote($this->rubric_id, "integer").",
                        ".$this->ilDB->quote($new_label['label'], "text").",
                        ".$this->ilDB->quote(number_format($new_label['weight'],2), "text").",
                        ".$this->ilDB->quote($sort_order, "integer").",
                        ".$this->ilDB->quote($_SESSION['AccountId'], "integer").",
                        NOW(),
                        NOW()
                    )"
                );
                
                $labels[$k]['rubric_label_id']=$new_rubric_label_id;
            }
            
            $sort_order++;
            
        }// foreach label
        
        return($labels);
    }
    
    private function saveRubricCardTbl()
    {
        /**
         *      Add/Update Rubric Card
         */
        
        //is there a rubric already for this?
        $set=$this->ilDB->query("select rubric_id from rubric where obj_id=".$this->ilDB->quote($this->obj_id, "integer")." and deleted is null");
        $row = $this->ilDB->fetchAssoc($set);
        $this->rubric_id=$row['rubric_id'];
        
        if(empty($this->rubric_id)){            
            $this->rubric_id=$this->incrementSequence('rubric_label_seq');
        }
        
        // insert or update the rubric        
        $this->ilDB->manipulate(
            "insert into rubric (rubric_id,obj_id,passing_grade,owner,create_date,last_update) values (
                ".$this->ilDB->quote($this->rubric_id, "integer").",                
                ".$this->ilDB->quote($this->obj_id, "integer").",
                ".$this->ilDB->quote($this->passing_grade, "integer").",
                ".$this->ilDB->quote($_SESSION['AccountId'], "integer").",                
                NOW(),
                NOW()
            ) on duplicate key update 
                last_update=NOW(),
                passing_grade=".$this->ilDB->quote($this->passing_grade, "integer").",
                owner=".$this->ilDB->quote($_SESSION['AccountId'], "integer")
        );
    }
}

?>