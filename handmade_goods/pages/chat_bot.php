<?php
$userAgent = $_SERVER['HTTP_USER_AGENT'];
if (preg_match('/Mobile|Android|iPhone/', $userAgent)) {
  $isMobile = true;
} else {
  $isMobile = false;
}
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<style>
  .chat-bot-button {
    position: fixed;
    bottom: 20px;       
    right: 19px;        
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: #4a6741;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 1001;
  }
  .chat-bot-button .material-symbols-outlined {
    color: white;
    font-size: 2rem;
  }
</style>


<?php if (!$isMobile): ?>
  <div class="chat-bot-button">
    <span class="material-symbols-outlined">support_agent</span>
  </div>
<?php endif; ?>

<script src="https://www.gstatic.com/dialogflow-console/fast/messenger/bootstrap.js?v=1"></script>
<df-messenger
  intent="WELCOME"
  chat-title="Support Agent"
  agent-id="b48dd9bd-8646-4373-be83-121f83934351"
  language-code="en">
</df-messenger>

