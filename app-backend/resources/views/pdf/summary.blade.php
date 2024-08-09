<!DOCTYPE html>
<html>
<head>
    <title>CIA - APPS PROJECT</title>
    <style>
    @page {
        size: landscape;
    }
    </style>
    
</head>
<body>
    @php

        $count_form = count($form);
        for($i=0; $i<$count_form; $i++){
            
    @endphp

            <!-- header -->
            <div>
                <table style="font-size: 12px; width:40%;">
                    <tr>
                        <td rowspan="3">
                            @php echo '<img src="'.$form[$i]['header']['logo'].'" width="116" height="69"/>'; @endphp
                        </td>
                        <td><b>@php echo $form[$i]['header']['title']; @endphp</b></td>
                    </tr>
                    <tr>
                        <td><span>@php echo $form[$i]['header']['subtitle']; @endphp</span></td>
                    </tr>
                    <tr>
                        <td><span>Periode: @php echo $form[$i]['header']['periode']; @endphp - Week @php echo $form[$i]['header']['week']; @endphp</span></td>
                    </tr>
                </table>
                <br>
            </div>
            <br>

            <!-- body -->
            <div>
                <table style="font-family: arial, sans-serif; font-size: 7px; border-collapse: collapse; width: 100%;">
                    <tr style="text-align: center; border: 1px solid #dddddd;" >
                        <th style="text-align: center; border: 1px solid #dddddd;" >NO</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Tanggal Pengajuan</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Karyawan</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Divisi</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Jenis Klaim Kesehatan</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Description</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >BANK</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Rekening</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Base Plafon</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Usage</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Balance</th>
                        <th style="text-align: center; border: 1px solid #dddddd;" >Total Amount</th>
                    </tr>

                    @php

                        $count_items = count($form[$i]['item']);
                        $no = 1;
                        $total = 0;
                        for($j=0; $j<$count_items; $j++){
                            $report = $form[$i]['item'][$j];
                            $total = $total + $report['total_amount'];
                            echo '<tr>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:2% word-wrap:" >'.$no.'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:2% word-wrap:" >'.$report['request_date'].'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:5% word-wrap:" >'.$report['emp_name'].'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:5% word-wrap:" >'.$report['division'].'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:5% word-wrap:" >'.$report['item'].'</td>';
                                echo '<td style="border: 1px solid #dddddd; width:5% word-wrap: break-word;" >'.$report['full_description'].'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:5% word-wrap:" >'.$report['bank_name'].'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:5% word-wrap:" >'.$report['bank_rekening'].'<br/>'.$report['bank_name'].'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:5% word-wrap:" >Rp. '.number_format($report['base_plafon'],2,",",".").'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:5% word-wrap:" >Rp. '.number_format($report['usage'],2,",",".").'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:5% word-wrap:" >Rp. '.number_format($report['balance'],2,",",".").'</td>';
                                echo '<td style="text-align: center; border: 1px solid #dddddd; width:5% word-wrap:" >Rp. '.number_format($report['total_amount'],2,",",".").'</td>';
                            echo '</tr>';

                            $no++;
                        }

                        echo '<tr>';
                            echo '<td colspan="11" style="text-align: right; border: 1px solid #dddddd;" ><b>Grand Total:</b></td>';
                            echo '<td style="text-align: center; border: 1px solid #dddddd;" >Rp. '.number_format($total,2,",",".").'</td>';
                        echo '</tr>';



                    @endphp


                </table>
            </div>
            <br>
            

            <!-- sign -->
            <div>
            <table style="width: 100%; font-size:12px; text-align: center;">
                    <tr>
                        <td>Proposed by,</td>
                        <td>Approved by,</td>
                        <td>Support By,</td>
                    </tr>
                    <tr>
                        <td><br><br><br><br><br><br></td>
                        <td><br><br><br><br><br><br></td>
                        <td><br><br><br><br><br><br></td>
                    </tr>
                    <tr>
                        <td>@php echo $form[$i]['header']['sign_1']; @endphp</td>
                        <td>@php echo $form[$i]['header']['sign_2']; @endphp</td>
                        <td>@php echo $form[$i]['header']['sign_3']; @endphp</td>
                    </tr>

                </table>
            </div>

    @php
            $next = $i+1;
            if(isset($form[$next])){
                echo '<div style = "display:block; clear:both; page-break-after:always;"></div>';
            }

        }

    @endphp


    
    

</body>
</html>