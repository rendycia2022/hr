<!DOCTYPE html>
<html>
<head>
    <title>CIA - APPS PROJECT</title>
</head>
<body>
    <div style="text-align: center;">
        <span><b><u>APPROVAL FORM</u></b></span>
        <br><span>{{ $header_document_number }}</span>
    </div>
    <br>
    <!-- header -->
    <div>
        <table style="font-size: 12px; width:100%;">
            <tr>
                <td nowrap style="width:10%;">Company Name</td>
                <td style="width:1%;">:</td>
                <td nowrap style="width:20%;">{{ $header_company }}</td>
                <td style="width:39%;"></td>
                <td nowrap style="text-align: right; width:10%;" >Employee Name :</td>
                <td nowrap style="width:20%;">{{ $bank_account }}</td>
            </tr>
            <tr>
                <td nowrap style="width:10%;">Division</td>
                <td style="width:1%;">:</td>
                <td nowrap style="width:20%;">{{ $header_division_name }}</td>
                <td style="width:39%;"></td>
                <td nowrap style="text-align: right; width:10%;" >Date :</td>
                <td nowrap style="width:20%;">{{ $header_date }}</td>
            </tr>
            <tr>
                <td nowrap style="width:10%;"></td>
                <td style="width:1%;"></td>
                <td nowrap style="width:20%;"></td>
                <td style="width:39%;"></td>
                <td nowrap style="text-align: right; width:10%;" ></td>
                <td nowrap style="width:20%;"></td>
            </tr>
            <tr>
                <td nowrap style="width:10%;"></td>
                <td style="width:1%;"></td>
                <td nowrap style="width:20%;"></td>
                <td style="width:39%;"></td>
                <td nowrap style="text-align: right; width:10%;" ></td>
                <td nowrap style="width:20%;"></td>
            </tr>
        </table>
    </div>
    <!-- body -->
    <div>
        <table style="font-family: arial, sans-serif; font-size: 8px; border-collapse: collapse; width: 100%;">
            <tr style="text-align: center; border: 1px solid #dddddd;" >
                <th style="text-align: center; border: 1px solid #dddddd;" >ITEM</th>
                <th style="text-align: center; border: 1px solid #dddddd;" >REMARKS</th>
                <th style="text-align: center; border: 1px solid #dddddd;" >QTY</th>
                <th style="text-align: center; border: 1px solid #dddddd;" >PRICE</th>
                <th style="text-align: center; border: 1px solid #dddddd;" >TOTAL PRICE</th>
            </tr>

            @php

                $count_items = count($items_list);
                $no = 1;
                $total = 0;
                for($i=0; $i<$count_items; $i++){
                    $total_price = $items_list[$i]['amount'] * 1;
                    $total = $total + $total_price;
                    echo '<tr>';
                        echo '<td style="text-align: center; border: 1px solid #dddddd; width:5%" >'.$no.'</td>';
                        echo '<td style="border: 1px solid #dddddd; word-wrap: break-word; width:30%" >'.$items_list[$i]['remarks'].'</td>';
                        echo '<td style="text-align: center; border: 1px solid #dddddd; width:5%" >1</td>';
                        echo '<td style="text-align: center; border: 1px solid #dddddd; width:20%" >Rp. '.number_format($items_list[$i]['amount'],2,",",".").'</td>';
                        echo '<td style="text-align: center; border: 1px solid #dddddd; width:40%" >Rp. '.number_format($total_price,2,",",".").'</td>';
                    echo '</tr>';

                    $no++;
                }

                echo '<tr>';
                    echo '<td colspan="4" style="text-align: right; border: 1px solid #dddddd;" ><b>Grand Total:</b></td>';
                    echo '<td style="text-align: center; border: 1px solid #dddddd;" >Rp. '.number_format($total,2,",",".").'</td>';
                echo '</tr>';



            @endphp

        </table>
    </div>
    <br>

    <!-- sign -->
    <div>
        <table style="width: 100%; font-size:12px">
            <tr>
                <td>{{ $date_sign }}</td>
                <td colspan="2"></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>Proposed by,</td>
                <td colspan="2">Support by, &nbsp; Date:</td>
                <td>Approved by,</td>
                <td>Date:</td>
                <td></td>
            </tr>
            <tr>
                <td><br><br><br><br><br><br></td>
                <td><br><br><br><br><br><br></td>
                <td><br><br><br><br><br><br></td>
                <td><br><br><br><br><br><br></td>
                <td><br><br><br><br><br><br></td>
            </tr>
            <tr>
                <td >Human Resource</td>
                <td colspan="2" >Finance</td>
                <td >Manager</td>
                <td >Director</td>
            </tr>
        </table>
    </div>

</body>
</html>