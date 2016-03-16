<?php
//The method injection used in this class is used in laravel 4. I am assuming that this is the laravel 4 framework
use Functions;
use NotificationSenderInterface;//call class from external source
use PersonRepositoryInterface;//call class from external source
use SystemUserRepositoryInterface;//call class from external source

//Entity and NotificationInterface have been removed because they have not beed defined within the class
class Notification {
    public function __construct(NotificationSenderInterface $notificationSender,//It's practice to have camel case in double barreled names
                                PersonRepositoryInterface $person,SystemUserRepositoryInterface $user)
    {
        parent::__construct();
        $this->notificationsender = $notificationSender;
        $this->person = $person;
        $this->user = $user;//instantiate class SystemUserRepositoryInterface
    }
    /*
      Input: The notification to generate with the user(s) to generate and send for
      Action: Send the generated notification either by Email or SMS
      Return: The sent status of the notification for each user
    */
    public function sendGeneratedNotification($notificationID, $entityID, $input, $sender) {//removed variable SysUserID
        $received = array('Received' => array(), 'Not Received' => array());
        if(count($input['recipients'], COUNT_RECURSIVE) == 1 && $entityID == 50) {
            $person = $this->person->show($input['recipients'][0]);
            if($input['NotificationMethodID'] == 1) {
                $input['SenderID'] = $sender;
                $input['RecipientID'] = $person->Email;
            }else if(empty($person->Email)){//remove if from within if
                return "This person doesn't have an email address";
            }
            else {
                $input['SenderID'] = Functions::getConstant('SENDER_SMS');
            }
            //$recipientSYSUserID = $person->SysUserID; remove variable call on SysUserID. SysUserID and senderID seems two calls from one person
            $input['EntityID'] = $person->member->ExpeditionMemberID;
            if(is_null($input['RecipientID']) || empty($input['RecipientID'])) {
                return "This person doesn't have a phone/mobile number";
            }
            //Generate the content for this notification
            /* $content = $this->notificationsender->generateContent($notificationID, $input['EntityID'], $input['recipients'][0], array());
             if($content[0]->RtnVal == 1) {
                 //Send the notification
                 //made correction to array
                 $notificationSender = $this->notificationsender->create(
                     $content[0]->NotificationMethodID,
                     $content[0]->RecipientID,
                     $content[0]->RecipientSYSUserID,
                     $content[0]->Subject,
                     $content[0]->Body,
                     $content[0]->EntityID,
                     $content[0]->EntityTypeID,
                     $content[0]->SYSUserID,
                     $content[0]->NotificationID,
                     $content[0]->SenderID,
                     $content[0]->TrackingCode
                 );
                 $received['Received'][] = $person->FullName . ' - ' . $content[0]->RecipientID;
             } else {
                 $received['Not Received'][] = $content[0]->Msg;
             }*///comment out one set of notification sender details
        } else {
            foreach ($input['recipients'] as $recipient) {
                $details = explode("_", $recipient);
                if(count($details) > 1) {
                    $person = $this->person->show($details[0]);
                    $memberID = $details[1];
                } else {
                    $person = $this->person->show($details[0]);
                    $memberID = $person->member->ExpeditionMemberID;
                }
                if($input['NotificationMethodID'] == 1) {
                    $input['SenderID'] = $input['Sender'];
                } else {
                    $input['SenderID'] = Functions::getConstant('SENDER_SMS');
                }
                //Generate the content for this notification
                $content = $this->notificationsender->generateContent($memberID, $person);//don't need array and other varianles
                if($content[0]->RtnVal == 1) {
                    $notificationSender = $this->notificationsender->create(
                    //made corrections in the Assoc array
                        $content[0]->NotificationMethodID,
                        $content[0]->RecipientID,
                        $content[0]->Details,
                        $content[0]->Subject,
                        $content[0]->Body,
                        $content[0]->MemberID,
                        $content[0]->EntityID,
                        $content[0]->NotificationID,
                        $content[0]->SenderID,
                        $content[0]->TrackingCode
                    );
                    //Failed to create notification queue item
                    if ($notificationSender !== true) {
                        $received['Not Received'][] = $person->FullName . ' - ' . $notificationSender;
                    } else if ($notificationSender === true){//made correction to conditional statement
                        $received['Received'][] = $person->FullName . ' - ' . $notificationSender;
                    }
                } else {//Failed to generate notification
                    $received['Not Received'][] = $person->FullName . ' - ' . $notificationSender;
                }
            }
        }
        return $received;
    }
}