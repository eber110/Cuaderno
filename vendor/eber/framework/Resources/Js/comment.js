// import { getRequestData } from '/resources/js/request-data.js';

// const dataSession = await getRequestData('/estado-sesion');

// export function comment(charLimit) {

//  /*Si no hay sesión activa no se ejecuta el algoritmo*/
//   if (!dataSession[0]) {

//     return;

//   }

//   const containerComment = document.querySelector("#section-comments");

//   if (containerComment) {
    
//     const contentComment = containerComment.querySelector("#content-comment");
//     const countChar = containerComment.querySelector("#char-comment");
//     let cantCommentInit = containerComment.querySelector("#cant-comment").innerText;
//     cantCommentInit = parseInt(cantCommentInit, 10);
//     const cantComment = containerComment.querySelector("#cant-comment");
//     const idPost = containerComment.querySelector(".comments");
//     const limit = charLimit;
//     countChar.innerText = `${limit}`;
//     contentComment.setAttribute('maxlength', limit);

//     const containerCommentPost = containerComment.querySelector("#comment-content-post");
//     let assignmentId = 1;

//     const templateCommentContentPost = document.querySelector("#template-comment-post");
//     //const templateCommentClone = templateCommentContentPost.cloneNode(true);
//     templateCommentContentPost.remove();

//     //condición de sticky form-comment
//     function formCommentPosition() {

//       const heightContainerComment = Math.floor(containerComment.getBoundingClientRect().bottom);
//       const heightWindow = window.innerHeight;
//       const formComment = containerComment.querySelector("#form-comment");
  
//       if (heightContainerComment < heightWindow) {
        
//         formComment.classList.remove("sticky");
  
//       }else{
          
//         formComment.classList.add("sticky");
//       }

//     }

//     formCommentPosition();

//     document.addEventListener("scroll", formCommentPosition);
//     //*************************** */

//     contentComment.addEventListener("input", () => {
  
//       const char = contentComment.value;
//       let characterCounter = char.length;
//       let subtractCharacters = limit - characterCounter;
//       countChar.innerText = `${subtractCharacters}`;
  
//       if (subtractCharacters == 0 || characterCounter >= limit) {
  
//         countChar.innerHTML = '<i class="fa-solid fa-ban color6"></i>';
//         contentComment.setAttribute('maxlength', limit);
  
//       }
  
//     });
  
//     containerComment.addEventListener("click", async (e) => {
  
//       e.preventDefault();
//       const event = e.target;
//       const btnSend = event.closest("#btn-comment");
  
//       if (btnSend) {
  
//         const msgComment = contentComment.value;
  
//         const data = {
//           'id_post_comment': idPost.id,
//           'id_parent_comment': '',
//           'comment_content': msgComment,
//         };

//         const dataOut = JSON.stringify(data);
        
//         if (msgComment !== '' && msgComment.length <= limit) {
  
//           try {

//             const response = await fetch('/registrar-comentario', {

//               method: 'POST',
//               headers: {
//                 'Content-Type': 'application/json',
//                 'X-Requested-With': 'create-comment'
//               },
//               body: dataOut,

//             });

//             const dataResponse = await response.json();

//             if (dataResponse !== false) {
              
//               const templateCommentClone = templateCommentContentPost.cloneNode(true);
//               contentComment.value = '';
//               countChar.innerText = `${limit}`;
//               cantCommentInit = cantCommentInit + 1;
//               cantComment.innerText = cantCommentInit;
//               //cantComment.classList.remove("hidden");
  
//               templateCommentClone.classList.remove("hidden");
//               templateCommentClone.id = `template-${assignmentId}`;
  
//               templateCommentClone.querySelector("#template-comment-user").innerHTML = `${dataResponse} <i class="fa-solid fa-circle x4"></i> `;
//               templateCommentClone.querySelector("#template-comment-date").innerText = 'Reciente';
//               templateCommentClone.querySelector("#template-comment-content").innerText = data.comment_content;
  
//               containerCommentPost.insertAdjacentElement("afterbegin", templateCommentClone);
//               assignmentId = assignmentId +1;
              
//             }


//           } catch (error) {
            
//             console.log(error);

//           }
          
//         }
  
//         return;
        
//       }
  
//     });

//   }

// }