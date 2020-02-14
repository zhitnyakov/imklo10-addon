#Тюнинг IM KLO 1.0

IM KLO версии 1.0 (и/или выше) - чудотворная штука.
Скорость работы радует, интерефейс улучшен, JS-интеграция - пушка. 

Я активно взял на вооружение JS-интеграцию.
Похоже, что таким видом интеграции пользуется все больше и больше арбитражников.

Поэтому хочу поделиться с вами несколькими костылями, которые я сделал для себя.

Судя по тому, как развивается IM KLO в последнее время, ближайшие апдейты
лишат нас надобности пользоваться подобными костылями. Но пока обновления готовятся,
предлагаю заюзать мою скромную доработку. 

##Чего мне не хватило?

- **Подгрузка HTML кода на лету**. 
Так, чтобы можно было получить поведение похожее на include в PHP.
На данный момент создается iframe с черной страницей поверх белой.
Это сопряжено с рядом неудобств. О них - ниже.
- **Невозможность пробросить параметры в URL**. Если твой трафик идет на ссылку
`http://site.ru/?pixel=12345`, и там человеку откроется черная страница в iframe,
то черная страница не получит параметр `pixel=12345`
- **Возможны проблемы с сохранением cookies**. Если ты продвинутый арбитран, то вполне возможно, что
ты сначала прокидываешь ID пикселя в ссылке, а затем сохраняешь его в cookies, чтобы в конечном счете отобразить
пиксель с нужным ID на странице Спасибо. Если черная страница подгружается в iframe опять же могут возникнуть сложности
с сохранением cookies. Не везде и не всегда. Но риск простучать по пустому пикселю или что-то в таком духе возрастает.
- **Относительно палевная ссылка на JS-скрипт кло@ки**. Это в большей степени
уже вопрос мистики и веры, но мне значительно более приятно думать, что
бот видит в HTML-коде белой страницы ссылку на фиктивный файл `jquery.min.js`, нежели `tracker.js`

##Как накатить доработку?

**ВНИМАНИЕ! Сделай резервные копии файлов, которые будешь заменять!**

- [Скачай файл](http://yandex.ru) `main.php` и скопируй в `application/controllers`
- [Скачай файл](http://yandex.ru) `routes.php` и скопируй в `application/config`

## Фича#1 - Кастомное название JS файла

Теперь ты можешь назвать JS файл кло@ки как тебе захочется.

Как сделать кастомную ссылку?

Ссылка начинается по одному из двух вариантов:

- `http://your-imklo-domain.ru/jscdn/` - вариант для клоаки через iframe
- `http://your-imklo-domain.ru/cdnjs/` - вариант для клоаки через подмену HTML на лету

Далее можете написать какое угодно название JS файла. Например, я хочу 
подгрузить кло в режиме подмены HTML и дать название JS файлу `jquery.min.js`. В таком случае я пишу ссылку:

 `http://your-imklo-domain.ru/jscdn/jquery.min.js`
 
 Вот список ходовых непалевных названий для JS файла:
 
 - `jquery.min.js`
 - `bootstrap.min.js`
 - `vue.min.js`
 - `moment.min.js`
 
 ##Фича#2 - Клоака через подмену HTML на лету
 
 Если ты подключишь кло по второму способу из Фичи#1, то теперь на странице
 не будет появляться iframe. Вместо этого скрипт подтянет html код черной страницы и заменит
 им текущий белый контент.
 
 Важно - **черная страница должна находиться на том же домене, что и белая**.
 
 Что это дает? Теперь не теряются параметры ссылки, нет проблем с установкой cookies.
 
 Что насчет минусов? В теории фб может c большей вероятностью спалить вас, так как теперь на странице нет ни грамма белой страницы.
 С другой стороны, черная страница заменяет белую только для "нормальных" посетителей, поэтому все должно быть ок =)
 
 ##PS
 
 Пишу эту статью в поезде, пока есть свободное время в дороге. Здесь нет Интернета, поэтому часть кода написано "наощупь".
 Отсюда что-то может не работать. Не кипятись, просто отпиши мне в телеграм `@deniszhitnyakov` и я починю баг ;)
 
 ##PS_2
 
 Если статья понравилась, то **СДЕЛАЙ РЕПОСТ**.
 
 Если статья понравилась очень, то [СДЕЛАЙ ДОНАТ](https://yandex.ru).
 