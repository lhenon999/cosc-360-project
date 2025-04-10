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
  
  .chat-container {
    position: fixed;
    bottom: 90px;       
    right: 20px;
    width: 450px;
    height: 600px;
    background-color: #ffffff;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    display: none;
    z-index: 1000;
    overflow: hidden;
  }
</style>

<div class="chat-bot-button">
  <span class="material-symbols-outlined">support_agent</span>
</div>

<div id="chat-container" class="chat-container">
  <p style="padding: 10px; margin: 0;">Chat content placeholder</p>
</div>

<script src="https://www.gstatic.com/dialogflow-console/fast/messenger/bootstrap.js?v=1"></script>
<df-messenger
  intent="WELCOME"
  chat-title="Support Agent"
  agent-id="b48dd9bd-8646-4373-be83-121f83934351"
  language-code="en">
</df-messenger>

