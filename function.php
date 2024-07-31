  <!-- Tiny for Edit Integrate  -->
                    <script>
                        const fetchApi = import("https://unpkg.com/@microsoft/fetch-event-source@2.0.1/lib/esm/index.js").then(module => module.fetchEventSource);
                        // Instead, an alternate method for retrieving the API key should be used.
                        const api_key = 'sk-proj-cjv4kGABTjUzt3k3rQ4dT3BlbkFJesD3gLym9sS37LJQgp24';

                        tinymce.init({
                        selector: '#myTextarea',
						branding: false,
                        plugins: 'ai preview powerpaste casechange importcss tinydrive searchreplace autolink autosave save directionality advcode visualblocks visualchars fullscreen image link media export mediaembed codesample table charmap pagebreak nonbreaking anchor tableofcontents insertdatetime advlist lists checklist wordcount tinymcespellchecker a11ychecker editimage help formatpainter permanentpen pageembed charmap tinycomments mentions quickbars linkchecker emoticons advtable footnotes mergetags autocorrect typography advtemplate markdown inlinecss',
                        mobile: {
                            plugins: 'preview powerpaste casechange importcss tinydrive searchreplace autolink autosave save directionality advcode visualblocks visualchars fullscreen image link media mediaembed codesample table charmap pagebreak nonbreaking anchor tableofcontents insertdatetime advlist lists checklist wordcount tinymcespellchecker a11ychecker help formatpainter pageembed charmap mentions quickbars linkchecker emoticons advtable footnotes mergetags autocorrect typography advtemplate',
                        },
                        tinydrive_token_provider: (success, failure) => {
                            success({ token: 'jwt-token' });
                        },
                        content_css : "css/custom_content.css",
                        content_style: `
                        a:-webkit-any-link {
                            color: -webkit-link;
                            cursor: pointer;
                            text-decoration: underline;
                        }
                        `,
                        tinydrive_upload_path: '/some/other/path',
                        menubar: 'file edit view insert format tools table tc help',
                        toolbar: "undo redo aidialog aishortcuts blocks fontsize | fontfamily | bold italic underline strikethrough | forecolor backcolor formatpainter removeformat | align indent outdent lineheight | checklist numlist bullist | link image media table mergetags pageembed | addcomment showcomments | spellcheckdialog a11ycheck typography | emoticons charmap | code fullscreen preview | save print | pagebreak anchor codesample footnotes | addtemplate inserttemplate | ltr rtl casechange",
                        autosave_ask_before_unload: true,
                        autosave_interval: '30s',
                        tinycomments_mode: 'embedded',
                        tinycomments_author: 'Author name',
                        mergetags_list: [
                        { value: 'First.Name', title: 'First Name' },
                        { value: 'Email', title: 'Email' },
                        ],
                        ai_request: (request, respondWith) => {
                            respondWith.stream((signal, streamMessage) => {
                            // Adds each previous query and response as individual messages
                            const conversation = request.thread.flatMap((event) => {
                                if (event.response) {
                                return [
                                    { role: 'user', content: event.request.query },
                                    { role: 'assistant', content: event.response.data }
                                ];
                                } else {
                                return [];
                                }
                            });

                            // System messages provided by the plugin to format the output as HTML content.
                            const pluginSystemMessages = request.system.map((content) => ({
                                role: 'system',
                                content
                            }));

                            const systemMessages = [
                                ...pluginSystemMessages,
                                // Additional system messages to control the output of the AI
                                { role: 'system', content: 'Remove lines with ``` from the response start and response end.' }
                            ]

                            // Forms the new query sent to the API
                            const content = request.context.length === 0 || conversation.length > 0
                                ? request.query
                                : `Question: ${request.query} Context: """${request.context}"""`;

                            const messages = [
                                ...conversation,
                                ...systemMessages,
                                { role: 'user', content }
                            ];

                            const requestBody = {
                                model: 'gpt-3.5-turbo',
                                temperature: 0.7,
                                max_tokens: 800,
                                messages,
                                stream: true
                            };

                            const openAiOptions = {
                                signal,
                                method: 'POST',
                                headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${api_key}`
                                },
                                body: JSON.stringify(requestBody)
                            };

                            const onopen = async (response) => {
                                if (response) {
                                const contentType = response.headers.get('content-type');
                                if (response.ok && contentType?.includes('text/event-stream')) {
                                    return;
                                } else if (contentType?.includes('application/json')) {
                                    const data = await response.json();
                                    if (data.error) {
                                    throw new Error(`${data.error.type}: ${data.error.message}`);
                                    }
                                }
                                } else {
                                throw new Error('Failed to communicate with the ChatGPT API');
                                }
                            };

                            // This function passes each new message into the plugin via the `streamMessage` callback.
                            const onmessage = (ev) => {
                                const data = ev.data;
                                if (data !== '[DONE]') {
                                const parsedData = JSON.parse(data);
                                const firstChoice = parsedData?.choices[0];
                                const message = firstChoice?.delta?.content;
                                if (message) {
                                    streamMessage(message);
                                }
                                }
                            };

                            const onerror = (error) => {
                                // Stop operation and do not retry by the fetch-event-source
                                throw error;
                            };

                            // Use microsoft's fetch-event-source library to work around the 2000 character limit
                            // of the browser `EventSource` API, which requires query strings
                            return fetchApi
                            .then(fetchEventSource =>
                                fetchEventSource('https://api.openai.com/v1/chat/completions', {
                                ...openAiOptions,
                                openWhenHidden: true,
                                    onopen,
                                onmessage,
                                onerror
                                })
                            )
                            .then(async (response) => {
                                if (response && !response.ok) {
                                const data = await response.json();
                                if (data.error) {
                                    throw new Error(`${data.error.type}: ${data.error.message}`);
                                }
                                }
                            })
                            .catch(onerror);
                            });
                        },
                        ai_shortcuts: [
                            { title: 'Screenplay', prompt: 'Convert this to screenplay format.', selection: true },
                            { title: 'Stage play', prompt: 'Convert this to stage play format.', selection: true },
                            { title: 'Classical', subprompts:
                                [
                                    { title: 'Dialogue', prompt: 'Convert this to a Socratic dialogue.', selection: true },
                                    { title: 'Homeric', prompt: 'Convert this to a Classical Epic.', selection: true }
                                ]
                            },
                            { title: 'Celtic', subprompts:
                                [
                                    { title: 'Bardic', prompt: 'Convert this to Bardic verse.', selection: true },
                                    { title: 'Filí', prompt: 'Convert this to Filí-an verse.', selection: true }
                                ]
                            },
                            { title: 'Summarize content', prompt: 'Provide the key points and concepts in this content in a succinct summary.', selection: true },
                            { title: 'Improve writing', prompt: 'Rewrite this content with no spelling mistakes, proper grammar, and with more descriptive language, using best writing practices without losing the original meaning.', selection: true },
                            { title: 'Simplify language', prompt: 'Rewrite this content with simplified language and reduce the complexity of the writing, so that the content is easier to understand.', selection: true },
                            { title: 'Expand upon', prompt: 'Expand upon this content with descriptive language and more detailed explanations, to make the writing easier to understand and increase the length of the content.', selection: true },
                            { title: 'Trim content', prompt: 'Remove any repetitive, redundant, or non-essential writing in this content without changing the meaning or losing any key information.', selection: true },
                            { title: 'Change tone', subprompts: [
                                { title: 'Professional', prompt: 'Rewrite this content using polished, formal, and respectful language to convey professional expertise and competence.', selection: true },
                                { title: 'Casual', prompt: 'Rewrite this content with casual, informal language to convey a casual conversation with a real person.', selection: true },
                                { title: 'Direct', prompt: 'Rewrite this content with direct language using only the essential information.', selection: true },
                                { title: 'Confident', prompt: 'Rewrite this content using compelling, optimistic language to convey confidence in the writing.', selection: true },
                                { title: 'Friendly', prompt: 'Rewrite this content using friendly, comforting language, to convey understanding and empathy.', selection: true },
                            ] },
                            { title: 'Change style', subprompts: [
                                { title: 'Business', prompt: 'Rewrite this content as a business professional with formal language.', selection: true },
                                { title: 'Legal', prompt: 'Rewrite this content as a legal professional using valid legal terminology.', selection: true },
                                { title: 'Journalism', prompt: 'Rewrite this content as a journalist using engaging language to convey the importance of the information.', selection: true },
                                { title: 'Medical', prompt: 'Rewrite this content as a medical professional using valid medical terminology.', selection: true },
                                { title: 'Poetic', prompt: 'Rewrite this content as a poem using poetic techniques without losing the original meaning.', selection: true },
                            ] }
                        ],
                        menu: {favs: { title: 'My Favorites', items: 'code visualaid | searchreplace | emoticons' }},
                        menubar: 'favs file edit view insert format tools table help',
                        setup: function (editor) {
                            editor.on('init', function (e) {
                                editor.setContent('<?php echo $padTitle; ?>');

                               const button = document.getElementById('generateTinyCont');
                               const outputTextArea = document.getElementById('output-text-area');
                                if (button) {
                                    button.addEventListener('click', () => {
                                        // alert('Testing..');
                                        const pluginAPI = tinymce.get(0).plugins.inlinecss;
                                        // const outputIframe = document.getElementById('outputIframe');
                                        // const rawDoc = outputIframe.contentWindow.document;
                                        pluginAPI.getContent().then((content) => {
                                            outputTextArea.value = content.html;

                                            // if (rawDoc) {
                                            // rawDoc.open();
                                            // rawDoc.write(content.html);
                                            // rawDoc.close();
                                            // }
                                        });
                                    });
                                }
                            });
                        }
                        });

                    </script>