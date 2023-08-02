<?php
defined( 'ABSPATH' ) or die( 'Hey, what are you doing here? You silly human!' );
?>
<style>
    #wpcontent {
        padding-left: 0 !important;
    }
    #wpbody-content {
        padding-bottom: 0 !important;
    }
    #wrap {
        background-color: rgb(3, 7, 21);
        font-family: sans-serif;
        min-height: 100dvh;
        width: 100%;
        display: flex;
    }
    #flow-window {
        flex: 1;
    }
    #add-window {
        width: 20%;
        position: relative;
    }
    #add-window > div {
        padding: 50px 20px;
        background-color: black;
        position: fixed;
        top: 0;
        right: 0;
        width: 20%;
        height: 100vh;
    }
    #add-window > div button {
        display: block;
        margin: 0 auto;
        border-radius: 4px;
        background-color: #00c7a6;
        border: none;
        color: #FFFFFF;
        text-align: center;
        transition: all 0.5s;
        cursor: pointer;
        padding: 10px;
        font-size: medium;
        width: 100px;
    }
    .step {
        background-color: #fff;
        color: #00c7a6;
        font-weight: bold;
        width: 100%;
        border-radius: 10px;
        max-width: 400px;
        padding: 1rem;
        display: block;
        margin: 5rem auto;
    }
    .step h1 {
        text-align: center;
    }
    .step h5 {
        color: #00c7a6;
        text-align: center;
    }
    .step textarea {
        width: 100%;
    }
    .step label,input {
        margin: 10px 0;
    }
    .step ul li {
        background-color: #00c7a6;
        border: 1px solid #000;
        color: black;
        padding: 5px 0;
        padding-left: 10px;
        position: relative;
    }
    .step ul li a {
        position: absolute;
        right: 5px;
        cursor: pointer;
        color: red;
        margin-left: 10px;
    }
    .add-option {
        margin-left: 10px;
        border-radius: 4px;
        background-color: #00c7a6;
        border: none;
        color: #FFFFFF;
        text-align: center;
        transition: all 0.5s;
        cursor: pointer;
        padding: 5px;
        font-size: medium;
    }
    .add-option-name {
        padding: 5px;
    }
    hr {
        border: none;
        height: 3px;
        background-color: #00c7a6;
    }
    .delete {
        display: block;
        margin: 5px auto;
        border-radius: 4px;
        background-color: #00c7a6;
        border: none;
        color: #FFFFFF;
        text-align: center;
        transition: all 0.5s;
        cursor: pointer;
        padding: 10px;
        font-size: medium;
        width: 100px;
        transition: all 0.5s;
    }
    .delete:hover {
        background-color: #009078;
    }
    .info {
        color: red;
    }
    #add-window > div  div {
        display: flex;
        align-items: center;
        justify-content: center;
        color: #00c7a6;
        font-weight: bold;
        margin: 10px 0;
    }
    #add-window select {
        margin-left: 10px;
        flex: 1;
    }
    #add-window input {
        margin-left: 10px;
        flex: 1;
        border: 1px solid #00c7a6;
        height: 1.5rem;
    }
    #add-window h1 {
        font-size: medium;
        display: block;
        color: #00c7a6;
        text-align: center;
    }
    #add-step-btn {
        margin-bottom: 4rem !important;
    }
    .warning {
        color: red;
        text-align: center;
    }
    #progress {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100dvh;
        background-color: #000000;
        color: #fff;
        font-size: medium;
        opacity: 0.5;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #open-ai-key {
        width: 100% !important;
    }
</style>
<div id="wrap">
    <div id="progress" style="display: none;">
    Loading...
    </div>
    <div id="flow-window">
        <div class="step openai">
            <h1>SETTINGS</h1>
            <hr/>
            <p>OPENAI KEY: </p>
            <input id="open-ai-key"/>
        </div>
    </div>
    <div id="add-window">
        <div id="sticky-window">
            <hr/>
            <h1>ADD STEP</h1>
            <hr/>
            <div>
                <label>TYPE: </label>
                <select id="step-type">
                    <option value="normal">NORMAL MESSAGE</option>
                    <option value="ai-msg">AI MESSAGE</option>
                    <option value="ai-qry">AI QUERY</option>
                    <option value="option">SEND OPTIONS</option>
                </select>
            </div>
            <div>
                <label>NAME: </label>
                <input id="step-name" class="ignore"/>
            </div>
            <button id="add-step-btn">ADD +</button>
            <p class="warning" id="add-warning"></p>
            <hr/>
            <h1>SAVE FLOW</h1>
            <hr/>
            <button id="save-flow-btn">SAVE</button>
            <p class="warning" id="save-warning"></p>
        </div>
    </div>
</div>


<script>
    const form = document.getElementById('gpt-form');
    const progress = document.getElementById('progress');
    const stickyWindow = document.getElementById('sticky-window');
    const flowWindow = document.getElementById("flow-window");
    progress.style.display = 'flex';
    stickyWindow.style.opacity = "0.5";
    stickyWindow.style.pointerEvents = "none";
    function createFlow(data) {
        data.forEach(function(step) {
            if ( step.type == 'openai') {
                const key = step.key;
                const settings = document.getElementById("open-ai-key");
                settings.value = key;
            }else if (step.type == 'normal') {
                const stepDiv = document.createElement('div');
                stepDiv.classList.add('step', 'normal');
                stepDiv.innerHTML = `<h1>${step.stepname}</h1>
                <hr/>
                <h5>NORMAL MESSAGE</h5>
                <p>MESSAGE: </p>
                <textarea rows="2">${step.msg}</textarea>
                <p class="info">This reply message will be send as it is</p>
                <button class="delete" onclick="deleteMe(this)">DELETE</button>
                `;
                flowWindow.appendChild(stepDiv);
            }else if (step.type == 'option') {
                const stepDiv = document.createElement('div');
                stepDiv.classList.add('step', 'option');
                const optionsList = step.options.map(option => `<li>${option} <a onclick="deleteOption(this)">DELETE</a></li>`).join('\n');

                stepDiv.innerHTML = `<h1>${step.stepname}</h1>
                <hr/>
                <h5>SEND OPTIONS</h5>
                <label>NAME: </label><input value="${step.optionname}"/>
                <p>OPTIONS: </p>
                <ul>
                    ${optionsList}
                </ul>
                <input class="add-option-name ignore"/><button onclick="addOption(this)" class="add-option">ADD +</button>
                <p class="info">These options will be sent to the user for choosing</p>
                <button class="delete" onclick="deleteMe(this)">DELETE</button>
                `;
                flowWindow.appendChild(stepDiv);
            }else if (step.type == 'ai-msg') {
                const stepDiv = document.createElement('div');
                stepDiv.classList.add('step', 'ai-msg');
                stepDiv.innerHTML = `<h1>${step.stepname}</h1>
                <hr/>
                <h5>AI MESSAGE</h5>
                <p>PROMPT: </p>
                <textarea rows="3">${step.prompt}</textarea>
                <p class="info">This reply will be generated by ai</p>
                <button class="delete" onclick="deleteMe(this)">DELETE</button>
                `;
                flowWindow.appendChild(stepDiv);
            }else if (step.type == 'ai-qry') {
                const stepDiv = document.createElement('div');
                stepDiv.classList.add('step', 'ai-qry');
                stepDiv.innerHTML = `<h1>${step.stepname}</h1>
                <hr/>
                <h5>AI QUERY</h5>
                <p>What Do You Want To Query For ?: </p>
                <input/ value="${step.query}">
                <p>SUCCESS: </p>
                <textarea rows="3">${step.success}</textarea>
                <p>ELSE: </p>
                <textarea rows="3">${step.elsecase}</textarea>
                <label>KEY: </label><input value="${step.key}"/>
                <p class="info">This message will ask user for into based on key name and query, Please enter a unique key name similar to your query</p>
                <button class="delete" onclick="deleteMe(this)">DELETE</button>
                `;
                flowWindow.appendChild(stepDiv);
            }
        });
    }
    jQuery.ajax({
            url: ajaxurl,
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce( 'get_gpt_flow' ) ?>'
            },
            type: 'GET',
            data: {
                'action': 'get_flow'
            },
            success: function(response) {
                console.log(response);
                const data = JSON.parse(response);
                createFlow(data);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('AJAX request failed: ', errorThrown);
            },
            complete: function() {
                progress.style.display = 'none';
                progress.innerText = "";
                stickyWindow.style.opacity = "1";
                stickyWindow.style.pointerEvents = "auto";
            }
        });
    function deleteOption(option) {
        parentLi = option.parentNode;
        parentUl = parentLi.parentNode;
        parentUl.removeChild(parentLi);
    }
    function addOption(button) {
        const parentUl = button.parentNode.querySelector("ul");
        const inputElement = button.previousElementSibling;
        const optionText = inputElement.value.trim();
        if (optionText !== "") {
            const newLi = document.createElement("li");
            newLi.innerHTML = optionText + '<a onclick="deleteOption(this)">DELETE</a>';
            parentUl.appendChild(newLi);
            inputElement.value = "";
        }
    }
    function deleteMe(element) {
        const step = element.closest('.step');
        step.remove();
    }
    const addStepBtn = document.getElementById("add-step-btn");
    addStepBtn.addEventListener("click", () => {
        const stepType = document.getElementById("step-type").value;
        stepNameInput = document.getElementById("step-name");
        const stepName = stepNameInput.value;
        if (stepName == "") {
            document.getElementById("add-warning").innerText = "Please enter a step name.";
            return;
        }
        document.getElementById("add-warning").innerText = "";
        const newStep = document.createElement("div");
        newStep.classList.add("step");
        if (stepType == "normal") {
            newStep.classList.add("normal");
            newStep.innerHTML = `<h1>${stepName.toUpperCase()}</h1>
            <hr/>
            <h5>NORMAL MESSAGE</h5>
            <p>MESSAGE: </p>
            <textarea rows="2"></textarea>
            <p class="info">This reply message will be send as it is</p>
            <button class="delete" onclick="deleteMe(this)">DELETE</button>`;
            flowWindow.appendChild(newStep);
            stepNameInput.value = "";
        } else if (stepType == "ai-msg") {
            newStep.classList.add("ai-msg");
            newStep.innerHTML = `<h1>${stepName.toUpperCase()}</h1>
            <hr/>
            <h5>AI MESSAGE</h5>
            <p>PROMPT: </p>
            <textarea rows="3"></textarea>
            <p class="info">This reply will be generated by ai</p>
            <button class="delete" onclick="deleteMe(this)">DELETE</button>`;
            flowWindow.appendChild(newStep);
            stepNameInput.value = "";
        } else if ( stepType == "ai-qry") {
            newStep.classList.add("ai-qry");
            newStep.innerHTML = `<h1>${stepName.toUpperCase()}</h1>
            <hr/>
            <h5>AI QUERY</h5>
            <p>What Do You Want To Query For ?: </p>
            <input/>
            <p>SUCCESS: </p>
            <textarea rows="3"></textarea>
            <p>ELSE: </p>
            <textarea rows="3"></textarea>
            <label>KEY: </label><input/>
            <p class="info">This message will ask user for into based on key name and query, Please enter a unique key name similar to your query</p>
            <button class="delete" onclick="deleteMe(this)">DELETE</button>`;
            flowWindow.appendChild(newStep);
            stepNameInput.value = "";
        } else if ( stepType == "option") {
            newStep.classList.add("option");
            newStep.innerHTML = `<h1>${stepName.toUpperCase()}</h1>
            <hr/>
            <h5>SEND OPTIONS</h5>
            <label>NAME: </label><input/>
            <p>OPTIONS: </p>
            <ul>
            </ul>
            <input class="add-option-name ignore"/><button onclick="addOption(this)" class="add-option">ADD +</button>
            <p class="info">These options will be sent to user for choosing</p>
            <button class="delete" onclick="deleteMe(this)">DELETE</button>`;
            flowWindow.appendChild(newStep);
            stepNameInput.value = "";
        }
    });
    const saveFlowBtn = document.getElementById("save-flow-btn");
    saveFlowBtn.addEventListener("click", () => {
        const inputs = document.querySelectorAll("input, textarea");
        const saveWarn = document.getElementById("save-warning");
        for (const input of inputs) {
            if (input.value == "") {
                if (input.classList.contains("ignore")) {
                    continue;
                }
                saveWarn.style.color = "red";
                saveWarn.innerText = "Please fill all the fields";
                return;
            }
        }
        progress.style.display = "flex";
        progress.innerHTML = "Saving... ";
        stickyWindow.style.opacity = "0.5";
        stickyWindow.style.pointerEvents = "none";
        saveWarn.innerText = "";
        let stepsData = [];
        const steps = document.querySelectorAll(".step");
        for (const step of steps) {
            const stepType = step.classList.item(1);
            data = {type: stepType}
            if (stepType == "openai") {
                data.type = "openai";
                data.key = step.querySelector("input").value.trim();
                stepsData.push(data);
            } else if (stepType == "normal") {
                data.stepname = step.querySelector("h1").innerText;
                data.msg = step.querySelector("textarea").value;
                stepsData.push(data);
            } else if (stepType == "ai-msg") {
                data.stepname = step.querySelector("h1").innerText;
                const task = step.querySelector("textarea").value;
                data.prompt = task;
                stepsData.push(data);
            } else if (stepType == "ai-qry") {
                data.stepname = step.querySelector("h1").innerText;
                const inputElements = step.querySelectorAll("input");
                const textareaElements = step.querySelectorAll("textarea")
                const query = inputElements[0].value.trim();
                data.query = query;
                const onPass = textareaElements[0].value.trim();
                data.success = onPass;
                const elseCase = textareaElements[1].value.trim();
                data.elsecase = elseCase;
                const key = inputElements[1].value.trim();
                data.key = key;
                data.prompt = '';
                stepsData.push(data);   
            } else if (stepType == "option") {
                data.stepname = step.querySelector("h1").innerText;
                const optionname = step.querySelector("input").value;
                data.optionname = optionname;
                const optionName = step.querySelector("input").value.trim();
                const options = []
                for (const option of step.querySelectorAll("ul li")) {
                    options.push(option.innerText.trim().split("\n")[0]);
                }
                if (options.length > 0) {
                    data.name = optionName;
                    data.options = options;
                    stepsData.push(data);
                }
            }

        }
        var nonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';
        jQuery.ajax({
            url: ajaxurl,
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce( 'save_gpt_flow' ) ?>'
            },
            type: 'POST',
            data: {
                'action': 'save_flow',
                'data': stepsData,
            },
            success: function(response) {
                console.log(response);
                saveWarn.style.color = 'green';
                saveWarn.innerText = "Data saved successfully";
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('AJAX request failed: ', errorThrown);
                saveWarn.style.color = 'red';
                saveWarn.innerText = "Something went wrong";
            },
            complete: function(jqXHR, textStatus) {
                progress.style.display = 'none';
                progress.innerHTML = "";
                stickyWindow.style.opacity = "1";
                stickyWindow.style.pointerEvents = "auto";
            }
        });
        
    });
</script>