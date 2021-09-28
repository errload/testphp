<?php

    class Order
    {
        public $event_id;
        public $event_date;
        public $ticket_adult_price;
        public $ticket_adult_quantity;
        public $ticket_kid_price;
        public $ticket_kid_quantity;
        public $barcode;

        // создаем конструктор с переданными полями
        public function __construct(
            $event_id, 
            $event_date, 
            $ticket_adult_price, 
            $ticket_adult_quantity, 
            $ticket_kid_price, 
            $ticket_kid_quantity)
        {
            $this->event_id = $event_id;
            $this->event_date = $event_date;
            $this->ticket_adult_price = $ticket_adult_price;
            $this->ticket_adult_quantity = $ticket_adult_quantity;
            $this->ticket_kid_price = $ticket_kid_price;
            $this->ticket_kid_quantity = $ticket_kid_quantity;
            // генерируем barcode со случайным непорядковым набором цифр
            $this->barcode = random_int(10000, 2000000000);
        }

        // бронь заказа на сторонний API
        protected function sendAPI()
        {
            // передаваемые параметры
            $params = [
                'event_id' => $this->event_id,
                'event_date' => $this->event_date,
                'ticket_adult_price' => $this->ticket_adult_price,
                'ticket_adult_quantity' => $this->ticket_adult_quantity,
                'ticket_kid_price' => $this->ticket_kid_price,
                'ticket_kid_quantity' => $this->ticket_kid_quantity,
                'barcode' => $this->barcode
            ];

            // случайный ответ, иммитация ответа API со случайным значением
            $randomResponse = [
                "message: 'order successfully booked'", 
                "error: 'barcode already exists'"
            ];

            // сам запрос
            $ch = curl_init('https://api.site.com/book');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            // рандомно берем ответ
            $response = $randomResponse[array_rand($randomResponse, 1)]; // curl_exec($ch);
            curl_close($ch);

            // если пришла ошибка, генерируем новый barcode и повторяем запрос к API
            if ($response == "error: 'barcode already exists'") {
                $this->barcode = random_int(10000, 2000000000);
                return $this->sendAPI();
            }

            // возвращаем barcode с успешным ответом
            return $this->barcode;
        }

        // подтверждение брони
        protected function sendConfirmAPI()
        {
            // единственный переданный параметр
            $params = ['barcode' => $this->barcode];

            // ответ либо положительный, либо любая из ошибок
            $randomResponse = [
                "message: 'order successfully aproved'", 
                "error: 'event cancelled'",
                "error: 'no tickets'",
                "error: 'no seats'",
                "error: 'fan removed'"
            ];

            $ch = curl_init('https://api.site.com/approve');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $response = $randomResponse[array_rand($randomResponse, 1)]; // curl_exec($ch);
            curl_close($ch);

            // если ответ пришел успешный, далее пойдет регистрация
            if ($response == "message: 'order successfully aproved'") return true;

            // иначе ничего не делаем
            return false;
        }

        public function addOrders()
        {
            // тут функция будет повторять себя пока не получит успех
            $this->sendAPI();
            
            // в случае неудачи данные в БД не заносятся
            if (!$this->sendConfirmAPI()) return;

            // соединяемся с базой
            include './connect.php';

            // общая сумма купленных билетов
            $sumPrice = ($this->ticket_adult_price * $this->ticket_adult_quantity) + ($this->ticket_kid_price * $this->ticket_kid_quantity);
            // текущая дата
            $date = date('Y-m-d H:m');

            // запись в БД
            $query = "
                INSERT INTO test_table 
                VALUES (
                    null,
                    '" . $this->event_id . "',
                    '" . $this->event_date . "',
                    '" . $this->ticket_adult_price . "',
                    '" . $this->ticket_adult_quantity . "',
                    '" . $this->ticket_kid_price . "',
                    '" . $this->ticket_kid_quantity . "',
                    '" . $this->barcode . "',
                    '" . $sumPrice . "',
                    '" . $date . "')";
            mysqli_query($link, $query) or die(mysqli_error($link));
        }
    }

    // ну и собственно приходящие данные
    $order = new Order('124', '30.09.2021', '1600', '2', '900', '3');
    $order->addOrders();

    /*
        можно было бы, конечно, еще и статикой воспользоваться
        Order::addOrders( ... )
        для красоты реализации.
    */