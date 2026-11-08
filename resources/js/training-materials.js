function numberQuestions(form){[...form.querySelectorAll('.material-question')].forEach((question,index)=>{question.querySelectorAll('[name]').forEach(field=>field.name=field.name.replace(/questions\[[^\]]+\]/,'questions['+index+']'));});}
document.addEventListener('click',event=>{
 const add=event.target.closest('[data-add-question]'),remove=event.target.closest('[data-remove-question]');
 if(add){const form=add.closest('form'),container=form.querySelector('[data-quiz-questions]');if(container.children.length>=30)return;container.append(document.getElementById('trainingQuestionTemplate').content.cloneNode(true));numberQuestions(form);container.lastElementChild.querySelector('input').focus();}
 if(remove){const form=remove.closest('form');remove.closest('.material-question').remove();numberQuestions(form);}
});
document.querySelectorAll('.material-form').forEach(form=>form.addEventListener('submit',event=>{numberQuestions(form);const participants=form.querySelectorAll('[name="participants[]"]');if(participants.length&&![...participants].some(field=>field.checked)){event.preventDefault();participants[0].setCustomValidity('Select at least one participant.');participants[0].reportValidity();participants[0].addEventListener('change',()=>participants[0].setCustomValidity(''),{once:true});}}));
